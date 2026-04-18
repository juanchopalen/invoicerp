<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class InvoiErpApiClient
{
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    public static function forAppUrl(): self
    {
        return new self(rtrim((string) config('app.url'), '/'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function emit(array $payload, string $bearer): Response
    {
        return Http::acceptJson()
            ->withToken($bearer)
            ->post($this->baseUrl.'/api/v1/documents/emit', $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function cancel(array $payload, string $bearer): Response
    {
        return Http::acceptJson()
            ->withToken($bearer)
            ->post($this->baseUrl.'/api/v1/documents/cancel', $payload);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    public function getJson(string $path, string $bearer, array $query = []): Response
    {
        return Http::acceptJson()
            ->withToken($bearer)
            ->get($this->baseUrl.$path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function postJson(string $path, array $payload, string $bearer): Response
    {
        return Http::acceptJson()
            ->withToken($bearer)
            ->post($this->baseUrl.$path, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function putJson(string $path, array $payload, string $bearer): Response
    {
        return Http::acceptJson()
            ->withToken($bearer)
            ->put($this->baseUrl.$path, $payload);
    }

    public function deleteJson(string $path, string $bearer): Response
    {
        return Http::acceptJson()
            ->withToken($bearer)
            ->delete($this->baseUrl.$path);
    }

    public static function freshBearerFor(User $user): string
    {
        return $user->createToken('filament-panel')->plainTextToken;
    }
}
