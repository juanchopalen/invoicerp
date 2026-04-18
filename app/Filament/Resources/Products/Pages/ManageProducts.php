<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\InvoicerpPanelApi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    unset($data['tenant_id']);
                    if (isset($data['currency']) && is_string($data['currency'])) {
                        $data['currency'] = strtoupper($data['currency']);
                    }
                    if (($data['sku'] ?? '') === '') {
                        $data['sku'] = null;
                    }
                    if (($data['serial'] ?? '') === '') {
                        $data['serial'] = null;
                    }

                    return $data;
                })
                ->using(function (array $data): Product {
                    $response = InvoicerpPanelApi::client()->postJson(
                        '/api/v1/products',
                        $data,
                        InvoicerpPanelApi::bearerForCurrentUser(),
                    );
                    InvoicerpPanelApi::haltUnlessSuccessful($response);
                    $id = $response->json('data.id');

                    return Product::query()->findOrFail((int) $id);
                }),
        ];
    }
}
