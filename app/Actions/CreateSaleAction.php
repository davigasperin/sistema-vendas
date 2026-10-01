<?php

namespace App\Actions;

use App\DTOs\CreateSaleDTO;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class CreateSaleAction
{
    public function __construct(
        private GenerateInstallmentsAction $generateInstallmentsAction
    ) {}

    public function __invoke(CreateSaleDTO $dto): Sale
    {
        return DB::transaction(function () use ($dto) {
            $productQuantities = [];
            foreach ($dto->items as $itemDTO) {
                $productId = $itemDTO->productId;
                $productQuantities[$productId] = ($productQuantities[$productId] ?? 0) + $itemDTO->quantity;
            }

            $products = Product::whereIn('id', array_keys($productQuantities))->lockForUpdate()->get()->keyBy('id');

            $sale = new Sale;
            $sale->forceFill([
                'user_id' => $dto->userId,
                'customer_id' => $dto->customerId,
                'payment_method_id' => $dto->paymentMethodId,
                'status' => SaleStatus::Completed,
                'installments' => $dto->installments,
                'discount' => $dto->discount,
                'notes' => $dto->notes,
                'total_amount' => 0,
            ])->save();

            $subtotalCents = 0;
            $mappedItems = [];

            foreach ($dto->items as $itemDTO) {
                $product = $products->get($itemDTO->productId);

                if (! $product || ! $product->active) {
                    throw new InsufficientStockException(
                        $product ? $product->name : 'Produto não encontrado',
                        $itemDTO->quantity,
                        $product ? $product->stock : 0
                    );
                }

                if ($product->stock < $itemDTO->quantity) {
                    throw new InsufficientStockException(
                        $product->name,
                        $itemDTO->quantity,
                        $product->stock
                    );
                }

                $unitPriceCents = (int) round((float) $product->price * 100);
                $subtotalItemCents = $unitPriceCents * $itemDTO->quantity;
                $subtotalCents += $subtotalItemCents;

                $mappedItems[] = [
                    'product' => $product,
                    'quantity' => $itemDTO->quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'subtotal_cents' => $subtotalItemCents,
                ];
            }

            $discountCents = (int) round($dto->discount * 100);
            if ($discountCents > $subtotalCents) {
                $discountCents = $subtotalCents;
            }

            $totalCents = $subtotalCents - $discountCents;

            $sale->total_amount = round($totalCents / 100, 2);
            $sale->discount = round($discountCents / 100, 2);
            $sale->save();

            foreach ($mappedItems as $mappedItem) {
                $product = $mappedItem['product'];
                $quantity = $mappedItem['quantity'];
                $previousStock = (int) $product->stock;
                $newStock = $previousStock - $quantity;

                SaleItem::create([
                    'sale_id' => $sale->id,
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
                    'reference_id' => $sale->id,
                    'reason' => "Venda #{$sale->id}",
                    'user_id' => $dto->userId,
                ]);
            }

            if ($dto->installments > 0) {
                ($this->generateInstallmentsAction)(
                    $sale,
                    $dto->installmentAmounts,
                    $dto->installmentDates,
                    $dto->installments
                );
            }

            return $sale;
        });
    }
}
