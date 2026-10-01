<?php

namespace App\Actions;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class CreateSaleAction
{
    public function __construct(
        private GenerateInstallmentsAction $generateInstallmentsAction
    ) {}

    public function __invoke(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $installmentAmounts = $data['installment_amounts'] ?? [];
            $installmentDates = $data['installment_dates'] ?? [];
            unset($data['installment_amounts'], $data['installment_dates']);

            $this->validateStock($items);

            $data['user_id'] = auth()->id();
            $data['total_amount'] = $this->calculateTotal($items, $data['discount'] ?? 0);

            $sale = new Sale();
            $sale->forceFill($data)->save();

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
                ($this->generateInstallmentsAction)(
                    $sale,
                    $installmentAmounts,
                    $installmentDates,
                    (int) ($data['installments'] ?? 1)
                );
            }

            return $sale;
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

    protected function calculateTotal(array $items, float $discount = 0): float
    {
        $subtotal = collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        return max(0, $subtotal - $discount);
    }
}