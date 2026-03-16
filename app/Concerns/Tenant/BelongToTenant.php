<?php

declare(strict_types=1);

namespace App\Concerns\Tenant;

use App\Models\Scopes\TenantScope;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongToTenant
{
    /**
     * Boot the trait — attach the global scope and auto-fill workspace_id on create.
     */
    public static function bootBelongToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model): void {
            $tenant = Workspace::current();

            if ($tenant && empty($model->workspace_id)) {
                $model->workspace_id = $tenant->id;
            }
        });
    }

    /**
     * Relationship back to the owning workspace.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Query without the tenant scope (useful for super-admin or cross-tenant reports).
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public static function withoutTenant(): \Illuminate\Database\Eloquent\Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
