<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): View
    {
        $products = $this->productService->getProductsPaginated(
            $request->filled('search') ? $request->search : null,
            $request->filled('active') ? $request->active : null
        );

        $stats = $this->productService->getStatistics();

        return view('products.index', [
            'products' => $products,
            'totalProducts' => $stats['total'],
            'activeProducts' => $stats['active'],
            'lowStock' => $stats['lowStock'],
        ]);
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(ProductRequest $request)
    {
        $this->productService->createProduct($request->validated());

        return redirect()->route('products.index')->with('success', 'Produto cadastrado com sucesso!');
    }

    public function show(Product $product): View
    {
        $stats = $this->productService->getProductStats($product);

        return view('products.show', [
            'product' => $product,
            'totalSold' => $stats['totalSold'],
            'totalRevenue' => $stats['totalRevenue'],
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $this->productService->updateProduct($product, $request->validated());

        return redirect()->route('products.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Product $product)
    {
        $this->productService->deleteProduct($product);

        return redirect()->route('products.index')->with('success', 'Produto excluído com sucesso!');
    }

    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $adjustment = (int) $request->get('adjustment', 0);
        $newStock = $this->productService->adjustStock($product, $adjustment);

        return response()->json([
            'success' => true,
            'stock' => $newStock,
            'message' => $newStock === 0 ? 'Estoque zerado!' : 'Estoque atualizado',
        ]);
    }

    public function toggleActive(Product $product): JsonResponse
    {
        $isActive = $this->productService->toggleActive($product);

        return response()->json([
            'success' => true,
            'active' => $isActive,
        ]);
    }

    public function searchApi(Request $request): JsonResponse
    {
        $term = $request->get('q', '');

        $query = Product::where('active', true)
            ->where('stock', '>', 0);

        if (empty($term)) {
            $products = $query->orderBy('name')->limit(5)->get(['id', 'name', 'price', 'stock']);
        } else {
            $products = $query->where('name', 'like', "%{$term}%")->limit(10)->get(['id', 'name', 'price', 'stock']);
        }

        return response()->json($products);
    }
}
