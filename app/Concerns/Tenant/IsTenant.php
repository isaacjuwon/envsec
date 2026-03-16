<?php

declare(strict_types=1);

namespace App\Concerns\Tenant;

use App\Events\Tenant\ForgettingTenant;
use App\Events\Tenant\UsingTenant;
use Illuminate\Support\Facades\Context;

trait IsTenant
{
    public function use(): void
    {
        UsingTenant::dispatch($this);
        app()->instance('tenant', $this);
        Context::add('tenantId', $this->id);

        if (request()->hasSession()) {
            request()->session()->put(config('tenancy.session_key', 'tenant_id'), $this->slug);
        }
    }

    /**
     * Clear this tenant from the current context.
     */
    public function forget(): void
    {
        ForgettingTenant::dispatch($this);
        app()->forgetInstance('tenant');
        Context::forget('tenantId');

        if (request()->hasSession()) {
            request()->session()->forget(config('tenancy.session_key', 'tenant_id'));
        }
    }

    /**
     * Run a callback scoped to this tenant, then restore the previous tenant (or forget).
     */
    public function run(callable $callback): mixed
    {
        $original = static::current();
        $this->use();

        try {
            return $callback($this);
        } finally {
            if ($original) {
                $original->use();
            } else {
                $this->forget();
            }
        }
    }

    /**
     * Get the currently active tenant, or null if none is set.
     */
    public static function current(): ?static
    {
        return app()->has('tenant') ? app('tenant') : null;
    }
}
