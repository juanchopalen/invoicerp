<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\StoreCustomerRequest;
use App\Http\Requests\API\V1\UpdateCustomerRequest;
use App\Models\Customer;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $customers = Customer::query()
            ->where('tenant_id', TenantContext::requireId())
            ->with(['country', 'state', 'city'])
            ->orderBy('legal_name')
            ->paginate($perPage);

        return response()->json($customers);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = TenantContext::requireId();
        $customer = Customer::query()->create($data);
        $customer->load(['country', 'state', 'city']);

        return response()->json(['data' => $customer], 201);
    }

    public function show(string $customer): JsonResponse
    {
        $record = $this->findTenantCustomer($customer);
        $record->load(['country', 'state', 'city']);

        return response()->json(['data' => $record]);
    }

    public function update(UpdateCustomerRequest $request, string $customer): JsonResponse
    {
        $record = $this->findTenantCustomer($customer);
        $record->update($request->validated());
        $record->load(['country', 'state', 'city']);

        return response()->json(['data' => $record]);
    }

    public function destroy(string $customer): JsonResponse
    {
        $this->findTenantCustomer($customer)->delete();

        return response()->json(null, 204);
    }

    private function findTenantCustomer(string $customerId): Customer
    {
        return Customer::query()
            ->where('tenant_id', TenantContext::requireId())
            ->whereKey($customerId)
            ->firstOrFail();
    }
}
