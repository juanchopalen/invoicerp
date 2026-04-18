<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\StoreApiClientRequest;
use App\Http\Requests\API\V1\UpdateApiClientRequest;
use App\Models\ApiClient;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ApiClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $clients = ApiClient::query()
            ->where('tenant_id', TenantContext::requireId())
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($clients);
    }

    public function store(StoreApiClientRequest $request): JsonResponse
    {
        [$plain, $prefix] = ApiClient::generateKeyPair();
        $client = ApiClient::query()->create([
            'tenant_id' => TenantContext::requireId(),
            'name' => $request->validated('name'),
            'status' => $request->validated('status'),
            'key_prefix' => $prefix,
            'api_key' => ApiClient::hashKey($plain),
        ]);

        return response()->json([
            'data' => $client->fresh(),
            'api_key_plain' => $plain,
        ], 201);
    }

    public function show(string $api_client): JsonResponse
    {
        $record = $this->findTenantApiClient($api_client);

        return response()->json(['data' => $record]);
    }

    public function update(UpdateApiClientRequest $request, string $api_client): JsonResponse
    {
        $record = $this->findTenantApiClient($api_client);
        $record->update($request->validated());

        return response()->json(['data' => $record->fresh()]);
    }

    private function findTenantApiClient(string $apiClientId): ApiClient
    {
        return ApiClient::query()
            ->where('tenant_id', TenantContext::requireId())
            ->whereKey($apiClientId)
            ->firstOrFail();
    }
}
