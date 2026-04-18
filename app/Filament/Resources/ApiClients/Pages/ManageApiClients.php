<?php

namespace App\Filament\Resources\ApiClients\Pages;

use App\Filament\Resources\ApiClients\ApiClientResource;
use App\Models\ApiClient;
use App\Support\InvoicerpPanelApi;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageApiClients extends ManageRecords
{
    protected static string $resource = ApiClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): ApiClient {
                    $response = InvoicerpPanelApi::client()->postJson(
                        '/api/v1/api-clients',
                        $data,
                        InvoicerpPanelApi::bearerForCurrentUser(),
                    );
                    InvoicerpPanelApi::haltUnlessSuccessful($response);
                    $plain = $response->json('api_key_plain');
                    if (is_string($plain) && $plain !== '') {
                        Notification::make()
                            ->title(__('Clave API (copiar ahora, se muestra una sola vez)'))
                            ->body($plain)
                            ->persistent()
                            ->success()
                            ->send();
                    }
                    $id = $response->json('data.id');

                    return ApiClient::query()->findOrFail((int) $id);
                }),
        ];
    }
}
