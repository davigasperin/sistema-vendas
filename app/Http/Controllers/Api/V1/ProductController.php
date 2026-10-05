<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $term = (string) $request->input('search');
            $query->where('name', 'like', "%{$term}%");
        }

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return ProductResource::collection($query->orderBy('name')->paginate($perPage));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $product = $this->productService->createProduct($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(ProductRequest $request, Product $product): ProductResource
    {
        $this->authorize('update', $product);

        $updated = $this->productService->updateProduct($product, $request->validated());

        return new ProductResource($updated);
    }

    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $this->authorize('adjustStock', $product);

        $request->validate([
            'adjustment' => 'required|integer',
        ]);

        $adjustment = (int) $request->input('adjustment');
        $newStock = $this->productService->adjustStock($product, $adjustment);

        return response()->json([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'stock' => $newStock,
                'adjustment' => $adjustment,
            ],
            'message' => 'Estoque atualizado com sucesso.',
        ]);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $term = (string) $request->input('q', '');

        $query = Product::where('active', true)->where('stock', '>', 0);
        if (! empty($term)) {
            $query->where('name', 'like', "%{$term}%");
        }

        return ProductResource::collection($query->orderBy('name')->limit(10)->get());
    }
}
