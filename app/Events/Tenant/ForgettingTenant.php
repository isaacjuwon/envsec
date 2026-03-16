<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use App\Models\Workspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ForgettingTenant
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Workspace $tenant,
    ) {}
}
