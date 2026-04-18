<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\StoreTenantRequest;
use App\Http\Requests\API\V1\UpdateTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $tenants = Tenant::query()
            ->with(['country', 'state', 'city'])
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($tenants);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = Tenant::query()->create($request->validated());
        $tenant->load(['country', 'state', 'city']);

        return response()->json(['data' => $tenant], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['country', 'state', 'city']);

        return response()->json(['data' => $tenant]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): JsonResponse
    {
        $tenant->update($request->validated());
        $tenant->load(['country', 'state', 'city']);

        return response()->json(['data' => $tenant]);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();

        return response()->json(null, 204);
    }
}
