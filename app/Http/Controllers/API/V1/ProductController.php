<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\StoreProductRequest;
use App\Http\Requests\API\V1\UpdateProductRequest;
use App\Models\Product;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $products = Product::query()
            ->where('tenant_id', TenantContext::requireId())
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = TenantContext::requireId();
        $product = Product::query()->create($data);

        return response()->json(['data' => $product], 201);
    }

    public function show(string $product): JsonResponse
    {
        $record = $this->findTenantProduct($product);

        return response()->json(['data' => $record]);
    }

    public function update(UpdateProductRequest $request, string $product): JsonResponse
    {
        $record = $this->findTenantProduct($product);
        $record->update($request->validated());

        return response()->json(['data' => $record->fresh()]);
    }

    public function destroy(string $product): JsonResponse
    {
        $this->findTenantProduct($product)->delete();

        return response()->json(null, 204);
    }

    private function findTenantProduct(string $productId): Product
    {
        return Product::query()
            ->where('tenant_id', TenantContext::requireId())
            ->whereKey($productId)
            ->firstOrFail();
    }
}
