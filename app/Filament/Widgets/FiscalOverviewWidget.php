<?php

namespace App\Filament\Widgets;

use App\Core\Domain\DocumentStatus;
use App\Models\FiscalDocument;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FiscalOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId === null) {
            return [];
        }

        $base = FiscalDocument::query()->where('tenant_id', $tenantId);

        return [
            Stat::make(__('Emitidos'), (clone $base)->where('status', DocumentStatus::Issued->value)->count()),
            Stat::make(__('Anulados'), (clone $base)->where('status', DocumentStatus::Cancelled->value)->count()),
            Stat::make(__('Total documentos'), (clone $base)->count()),
        ];
    }
}
