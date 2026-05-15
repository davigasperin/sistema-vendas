<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductService
{
    public function getProductsWithFilters(array $filters): Collection
    {
        $query = Product::query();

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['active'])) {
            if ($filters['active'] === '1') {
                $query->where('active', true);
            } elseif ($filters['active'] === '0') {
                $query->where('active', false);
            }
        }

        return $query->orderBy('name')->get();
    }

    public function getProductsPaginated(?string $search = null, ?string $active = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::query();

        if (!empty($search)) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        if (!is_null($active)) {
            if ($active === '1') {
                $query->where('active', true);
            } elseif ($active === '0') {
                $query->where('active', false);
            }
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function getStatistics(): array
    {
        return [
            'total' => Product::count(),
            'active' => Product::where('active', true)->count(),
            'lowStock' => Product::whereColumn('stock', '<=', 'low_stock_threshold')->where('active', true)->count(),
        ];
    }

    public function getActiveProducts(): Collection
    {
        return Product::where('active', true)->orderBy('name')->get();
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);
        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }

    public function adjustStock(Product $product, int $adjustment): int
    {
        $newStock = max(0, $product->stock + $adjustment);
        $product->update(['stock' => $newStock]);
        return $newStock;
    }

    public function toggleActive(Product $product): bool
    {
        $product->update(['active' => !$product->active]);
        return $product->active;
    }

    public function getProductStats(Product $product): array
    {
        $product->load(['saleItems.sale.customer', 'saleItems.sale.user']);
        
        return [
            'totalSold' => $product->saleItems->sum('quantity'),
            'totalRevenue' => $product->saleItems->sum(function ($item) {
                return $item->quantity * $item->unit_price;
            }),
        ];
    }

    public function isLowStock(Product $product): bool
    {
        return $product->active && $product->stock <= ($product->low_stock_threshold ?? 5);
    }
}