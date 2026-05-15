<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Produto não encontrado'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
        ]);
    }

    public function search(Request $request): JsonResponse
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