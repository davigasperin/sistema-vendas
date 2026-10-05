<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function getProductsWithFilters(array $filters): Collection
    {
        $query = Product::query();

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
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

        if (! empty($search)) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if (! is_null($active)) {
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
        $previousStock = (int) $product->stock;
        $newStock = max(0, $product->stock + $adjustment);
        $product->update(['stock' => $newStock]);

        Log::info('Ajuste manual de estoque', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'adjustment' => $adjustment,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'user_id' => auth()->id(),
        ]);

        return $newStock;
    }

    public function toggleActive(Product $product): bool
    {
        $product->update(['active' => ! $product->active]);

        return $product->active;
    }

    public function getProductStats(Product $product): array
    {
        /** @var object{total_sold: numeric, total_revenue: numeric}|null $stats */
        $stats = $product->saleItems()
            ->toBase()
            ->selectRaw('COALESCE(SUM(quantity), 0) as total_sold, COALESCE(SUM(subtotal), 0) as total_revenue')
            ->first();

        return [
            'totalSold' => (int) ($stats->total_sold ?? 0),
            'totalRevenue' => (float) ($stats->total_revenue ?? 0.0),
        ];
    }

    public function isLowStock(Product $product): bool
    {
        return $product->active && $product->stock <= ($product->low_stock_threshold ?? 5);
    }
}
