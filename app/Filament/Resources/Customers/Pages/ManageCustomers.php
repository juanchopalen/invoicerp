<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Support\InvoicerpPanelApi;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    unset($data['tenant_id']);

                    return $data;
                })
                ->using(function (array $data): Customer {
                    $response = InvoicerpPanelApi::client()->postJson(
                        '/api/v1/customers',
                        $data,
                        InvoicerpPanelApi::bearerForCurrentUser(),
                    );
                    InvoicerpPanelApi::haltUnlessSuccessful($response);
                    $id = $response->json('data.id');

                    return Customer::query()->findOrFail((int) $id);
                }),
        ];
    }
}
