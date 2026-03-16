<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Workspace;
use Illuminate\Http\Request;

class Tenant
{
    /**
     * Configure tenancy global settings.
     */
    public static function configure(?bool $forcePath = null): void
    {
        if ($forcePath !== null) {
            config(['tenancy.force_path' => $forcePath]);
        }
    }

    /**
     * Configure Livewire to be tenant-aware.
     */
    public static function livewire(): void
    {
        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            return \Illuminate\Support\Facades\Route::post('/{tenant?}/livewire/update', $handle)
                ->middleware([
                    'web',
                    \App\Http\Middleware\SetTenantMiddleware::class,
                ])
                ->where(['tenant' => '^(?!livewire|api|auth|onboarding|_boost|up).*$']);
        });
    }

    /**
     * Wrap routes with tenancy support (optional prefix and middleware).
     */
    public static function routes(callable $callback): void
    {
        \Illuminate\Support\Facades\Route::middleware([
            'auth',
            'verified',
            \App\Http\Middleware\SetTenantMiddleware::class,
        ])
        ->prefix('{tenant?}')
        ->where(['tenant' => '^(?!livewire|api|auth|onboarding|_boost|up).*$'])
        ->group($callback);
    }

    /**
     * Resolve the tenant using configured strategies.
     */
    public static function resolve(Request $request): ?Workspace
    {
        $strategies = config('tenancy.resolution_strategy', ['path', 'session', 'host']);

        foreach ($strategies as $strategy) {
            $tenant = match ($strategy) {
                'path' => static::fromPath($request),
                'session' => static::fromSession($request),
                'host' => static::fromHost($request),
                default => null,
            };

            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Resolve the tenant from the URL path.
     */
    public static function fromPath(Request $request): ?Workspace
    {
        $slug = $request->segment(1);

        if (!$slug) {
            return null;
        }

        return Workspace::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Resolve the tenant from the session.
     */
    public static function fromSession(Request $request): ?Workspace
    {
        if (!$request->hasSession()) {
            return null;
        }

        $slug = $request->session()->get(config('tenancy.session_key', 'tenant_id'));

        if (!$slug) {
            return null;
        }

        return Workspace::where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Resolve the tenant from the request host.
     */
    public static function fromHost(Request $request): ?Workspace
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        // Subdomain detection
        if (count($parts) > 1) {
            $subdomain = $parts[0];

            if ($subdomain !== 'www') {
                $tenant = Workspace::where('slug', $subdomain)
                    ->where('is_active', true)
                    ->first();

                if ($tenant) {
                    return $tenant;
                }
            }
        }

        // Fallback to exact custom domain match
        return Workspace::where('domain', $host)
            ->where('is_active', true)
            ->first();
    }
    /**
     * Ensure the request is using the correct tenanted path if required.
     */
    public static function ensureTenantPath(Request $request, Workspace $tenant): ?\Illuminate\Http\RedirectResponse
    {
        if (! config('tenancy.force_path', true)) {
            return null;
        }

        $segments = $request->segments();
        $firstSegment = $segments[0] ?? null;

        if ($firstSegment !== $tenant->slug && static::shouldRedirectToTenantPath($request)) {
            // Remove existing tenant slug if it exists in first position
            if ($firstSegment && Workspace::where('slug', $firstSegment)->exists()) {
                array_shift($segments);
            }
            
            return redirect()->to($tenant->slug . '/' . implode('/', $segments));
        }

        return null;
    }

    /**
     * Determine if the request should be redirected to a tenanted path.
     */
    protected static function shouldRedirectToTenantPath(Request $request): bool
    {
        return ! $request->is('onboarding', 'auth/*', 'api/*', 'livewire*', '_boost/*', 'up');
    }
}
