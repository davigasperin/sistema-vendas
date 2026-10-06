<?php

namespace App\Actions;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\Domain\SaleCancellationException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Services\AccountingPeriodService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelSaleAction
{
    public function __construct(private AccountingPeriodService $accountingPeriodService) {}

    public function __invoke(Sale $sale, ?int $userId = null): Sale
    {
        $cancelledSale = DB::transaction(function () use ($sale, $userId) {
            $this->accountingPeriodService->lockForUpdate();

            $lockedSale = Sale::where('id', $sale->id)->lockForUpdate()->firstOrFail();
            $this->accountingPeriodService->assertOpen($lockedSale->created_at);

            if ($lockedSale->status === SaleStatus::Cancelled) {
                throw SaleCancellationException::alreadyCancelled($lockedSale->id);
            }

            $hasPaidInstallments = $lockedSale->saleInstallments()
                ->where('is_paid', true)
                ->exists();

            if ($hasPaidInstallments) {
                throw SaleCancellationException::hasPaidInstallments($lockedSale->id);
            }

            $items = $lockedSale->items()->get();

            foreach ($items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();
                $previousStock = (int) $product->stock;
                $newStock = $previousStock + $item->quantity;

                $product->stock = $newStock;
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::SaleCancel,
                    'quantity' => $item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reference_type' => Sale::class,
                    'reference_id' => $lockedSale->id,
                    'reason' => "Cancelamento da venda #{$lockedSale->id}",
                    'user_id' => $userId ?? auth()->id(),
                ]);
            }

            $lockedSale->saleInstallments()
                ->where('is_paid', false)
                ->update(['notes' => 'Cancelada junto com a venda']);

            $lockedSale->status = SaleStatus::Cancelled;
            $lockedSale->save();

            return $lockedSale->fresh();
        });

        Log::info('Venda cancelada', [
            'sale_id' => $cancelledSale->id,
            'user_id' => $userId ?? auth()->id(),
            'total' => $cancelledSale->total_amount,
        ]);

        return $cancelledSale;
    }
}
