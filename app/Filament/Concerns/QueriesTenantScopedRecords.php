<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait QueriesTenantScopedRecords
{
    /**
     * Limit Filament queries to the authenticated user's tenant (defense in depth).
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected static function scopeQueryToCurrentTenant(Builder $query): Builder
    {
        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('tenant_id', $tenantId);
    }
}
