<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): Response
    {
        $products = $this->productService->getProductsPaginated(
            $request->filled('search') ? $request->search : null,
            $request->filled('active') ? $request->active : null
        );

        $stats = $this->productService->getStatistics();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'totalProducts' => $stats['total'],
            'activeProducts' => $stats['active'],
            'lowStock' => $stats['lowStock'],
            'filters' => $request->only(['search', 'active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create');
    }

    public function store(ProductRequest $request)
    {
        $this->productService->createProduct($request->validated());

        return redirect()->route('products.index')->with('success', 'Produto cadastrado com sucesso!');
    }

    public function show(Product $product): Response
    {
        $stats = $this->productService->getProductStats($product);

        return Inertia::render('Products/Show', [
            'product' => $product,
            'totalSold' => $stats['totalSold'],
            'totalRevenue' => $stats['totalRevenue'],
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', compact('product'));
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

    public function adjustStock(Request $request, Product $product)
    {
        $adjustment = (int) $request->get('adjustment', 0);
        $this->productService->adjustStock($product, $adjustment);

        return back()->with('success', 'Estoque atualizado!');
    }

    public function toggleActive(Product $product)
    {
        $this->productService->toggleActive($product);

        return back()->with('success', 'Status atualizado!');
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
