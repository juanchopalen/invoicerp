<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Support\InvoicerpPanelApi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTenants extends ManageRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): Tenant {
                    $response = InvoicerpPanelApi::client()->postJson(
                        '/api/v1/tenants',
                        $data,
                        InvoicerpPanelApi::bearerForCurrentUser(),
                    );
                    InvoicerpPanelApi::haltUnlessSuccessful($response);
                    $id = $response->json('data.id');

                    return Tenant::query()->findOrFail((int) $id);
                }),
        ];
    }
}
