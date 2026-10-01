<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class UpdateSaleAction
{
    public function __invoke(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            $items = $data['items'];
            unset($data['items']);

            $this->restoreStock($sale->items);
            $this->validateStock($items);

            $data['total_amount'] = $this->calculateTotal($items, $data['discount'] ?? 0);
            $sale->update($data);

            $sale->items()->delete();
            $sale->saleInstallments()->delete();

            foreach ($items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                ]);

                Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
            }

            if (($data['installments'] ?? 1) > 0) {
                $installmentAction = new GenerateInstallmentsAction;
                $installmentAction(
                    $sale,
                    $data['installment_amounts'] ?? [],
                    $data['installment_dates'] ?? [],
                    (int) ($data['installments'] ?? 1)
                );
            }

            return $sale->fresh();
        });
    }

    protected function validateStock(array $items): void
    {
        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->stock < $item['quantity']) {
                throw new InsufficientStockException(
                    $product->name,
                    $item['quantity'],
                    $product->stock
                );
            }
        }
    }

    protected function restoreStock($items): void
    {
        foreach ($items as $item) {
            Product::where('id', $item->product_id)->increment('stock', $item->quantity);
        }
    }

    protected function calculateTotal(array $items, float $discount = 0): float
    {
        $subtotal = collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        return max(0, $subtotal - $discount);
    }
}
