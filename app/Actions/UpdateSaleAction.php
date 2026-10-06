<?php

namespace App\Actions;

use App\Enums\StockMovementType;
use App\Exceptions\Domain\SaleCancellationException;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\AccountingPeriodService;
use Illuminate\Support\Facades\DB;

class UpdateSaleAction
{
    public function __construct(
        private GenerateInstallmentsAction $generateInstallmentsAction,
        private AccountingPeriodService $accountingPeriodService,
    ) {}

    public function __invoke(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            $this->accountingPeriodService->lockForUpdate();

            $lockedSale = Sale::where('id', $sale->id)->lockForUpdate()->firstOrFail();
            $this->accountingPeriodService->assertOpen($lockedSale->created_at);

            $hasPaidInstallments = $lockedSale->saleInstallments()
                ->where('is_paid', true)
                ->exists();

            if ($hasPaidInstallments) {
                throw SaleCancellationException::hasPaidInstallments($lockedSale->id);
            }

            $newItems = $data['items'] ?? [];
            unset($data['items']);

            $installmentAmounts = $data['installment_amounts'] ?? [];
            $installmentDates = $data['installment_dates'] ?? [];
            unset($data['installment_amounts'], $data['installment_dates']);

            $oldItems = $lockedSale->items()->get();

            $allProductIds = array_unique(array_merge(
                $oldItems->pluck('product_id')->all(),
                array_column($newItems, 'product_id')
            ));

            $products = Product::whereIn('id', $allProductIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // 1. Restore previous stock
            foreach ($oldItems as $oldItem) {
                $product = $products->get($oldItem->product_id);
                if ($product) {
                    $previousStock = (int) $product->stock;
                    $newStock = $previousStock + $oldItem->quantity;
                    $product->stock = $newStock;
                    $product->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => StockMovementType::Correction,
                        'quantity' => $oldItem->quantity,
                        'previous_stock' => $previousStock,
                        'new_stock' => $newStock,
                        'reference_type' => Sale::class,
                        'reference_id' => $lockedSale->id,
                        'reason' => "Estorno de itens para atualização da venda #{$lockedSale->id}",
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            // 2. Validate and calculate new items using backend price authority
            $subtotalCents = 0;
            $mappedItems = [];

            foreach ($newItems as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];
                $product = $products->get($productId);

                if (! $product || ! $product->active) {
                    throw new InsufficientStockException(
                        $product ? $product->name : 'Produto não encontrado',
                        $quantity,
                        $product ? $product->stock : 0
                    );
                }

                if ($product->stock < $quantity) {
                    throw new InsufficientStockException(
                        $product->name,
                        $quantity,
                        $product->stock
                    );
                }

                $unitPriceCents = (int) round((float) $product->price * 100);
                $subtotalItemCents = $unitPriceCents * $quantity;
                $subtotalCents += $subtotalItemCents;

                $mappedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'subtotal_cents' => $subtotalItemCents,
                ];
            }

            $discount = isset($data['discount']) ? (float) $data['discount'] : (float) $lockedSale->discount;
            $discountCents = (int) round($discount * 100);
            if ($discountCents > $subtotalCents) {
                $discountCents = $subtotalCents;
            }

            $totalCents = $subtotalCents - $discountCents;

            $data['total_amount'] = round($totalCents / 100, 2);
            $data['discount'] = round($discountCents / 100, 2);

            $lockedSale->update($data);

            $lockedSale->items()->delete();
            $lockedSale->saleInstallments()->delete();

            // 3. Decrement new stock and persist items
            foreach ($mappedItems as $mappedItem) {
                $product = $mappedItem['product'];
                $quantity = $mappedItem['quantity'];
                $previousStock = (int) $product->stock;
                $newStock = $previousStock - $quantity;

                SaleItem::create([
                    'sale_id' => $lockedSale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => round($mappedItem['unit_price_cents'] / 100, 2),
                    'subtotal' => round($mappedItem['subtotal_cents'] / 100, 2),
                ]);

                $product->stock = $newStock;
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::Sale,
                    'quantity' => -$quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reference_type' => Sale::class,
                    'reference_id' => $lockedSale->id,
                    'reason' => "Itens atualizados da venda #{$lockedSale->id}",
                    'user_id' => auth()->id(),
                ]);
            }

            $installmentsCount = (int) ($data['installments'] ?? $lockedSale->installments);
            if ($installmentsCount > 0) {
                ($this->generateInstallmentsAction)(
                    $lockedSale,
                    $installmentAmounts,
                    $installmentDates,
                    $installmentsCount
                );
            }

            return $lockedSale->fresh();
        });
    }
}
