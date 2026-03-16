<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Workspace;
use App\Support\Tenant;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureQueues();

        // Apply global tenancy configuration
        Tenant::configure(
            forcePath: config('tenancy.force_path', true)
        );

        // Make Livewire tenant-aware
        Tenant::livewire();
    }

    /**
     * Ensure queued jobs run within the correct tenant context.
     * The tenant ID is propagated automatically via Laravel Context.
     */
    public function configureQueues(): void
    {
        // Before each job: restore the tenant from context
        Queue::before(function (JobProcessing $event) {
            $id = Context::get('tenantId');

            if ($id) {
                Workspace::find($id)?->use();
            } else {
                Workspace::current()?->forget();
            }
        });

        // After each job: clear the tenant context to prevent bleed into the next job
        Queue::after(function () {
            Workspace::current()?->forget();
        });

        Queue::failing(function () {
            Workspace::current()?->forget();
        });
    }
}
