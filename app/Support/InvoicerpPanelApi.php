<?php

namespace App\Support;

use App\Models\User;
use App\Services\InvoiErpApiClient;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Http\Client\Response;

final class InvoicerpPanelApi
{
    public static function client(): InvoiErpApiClient
    {
        return InvoiErpApiClient::forAppUrl();
    }

    public static function bearerForCurrentUser(): string
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            throw new \RuntimeException('No authenticated user.');
        }

        return InvoiErpApiClient::freshBearerFor($user);
    }

    public static function haltUnlessSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $body = $response->json();
        $message = is_array($body) && isset($body['message']) && is_string($body['message'])
            ? $body['message']
            : $response->body();

        Notification::make()
            ->title(__('Error de API'))
            ->body(mb_substr($message, 0, 4000))
            ->danger()
            ->send();

        throw new Halt;
    }
}
