<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantMiddleware
{
    /**
     * Handle an incoming request.
     * Resolves the tenant from the subdomain or custom domain and activates it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = \App\Support\Tenant::resolve($request);

        if ($tenant) {
            $tenant->use();

            // Transparently handle the 'tenant' parameter
            \Illuminate\Support\Facades\URL::defaults(['tenant' => $tenant->slug]);
            
            if ($request->route()) {
                $request->route()->forgetParameter('tenant');
            }

            if ($redirect = \App\Support\Tenant::ensureTenantPath($request, $tenant)) {
                return $redirect;
            }
        } elseif (auth()->check() && !$request->routeIs('onboarding') && !$request->is('livewire*', 'api/*', 'auth/*')) {
            $user = auth()->user();
            $workspace = $user->ownedWorkspaces()->first() ?? $user->workspaces()->first();

            if ($workspace) {
                return redirect()->to($workspace->slug . '/dashboard');
            }

            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
