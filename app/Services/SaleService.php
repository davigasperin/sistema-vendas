<?php

namespace App\Services;

use App\Actions\CancelSaleAction;
use App\Actions\CreateSaleAction;
use App\Actions\UpdateSaleAction;
use App\DTOs\CreateSaleDTO;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Queries\SalesReportQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private CreateSaleAction $createSaleAction,
        private UpdateSaleAction $updateSaleAction,
        private CancelSaleAction $cancelSaleAction,
        private SalesReportQuery $salesReportQuery
    ) {}

    public function createSale(array $data): Sale
    {
        $userId = (int) (auth()->id() ?? $data['user_id'] ?? 1);
        $dto = CreateSaleDTO::fromArray($data, $userId);

        return ($this->createSaleAction)($dto);
    }

    public function updateSale(Sale $sale, array $data): Sale
    {
        return ($this->updateSaleAction)($sale, $data);
    }

    public function getSalesFiltered(Request $request): array
    {
        $query = Sale::with(['customer', 'paymentMethod', 'user']);

        if ($request->boolean('trashed')) {
            $query->withTrashed();
        } else {
            $query->whereNull('deleted_at');
        }

        $query->orderBy('created_at', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('payment_method_id')) {
            $query->where('payment_method_id', $request->payment_method_id);
        }

        $sales = $query->paginate(15);
        $customers = Customer::orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('active', true)->get();

        return [
            'sales' => $sales,
            'customers' => $customers,
            'paymentMethods' => $paymentMethods,
        ];
    }

    public function getSalesForCreate(): array
    {
        $paymentMethods = PaymentMethod::where('active', true)->orderBy('name')->get();

        return [
            'paymentMethods' => $paymentMethods,
        ];
    }

    public function getSalesForEdit(Sale $sale): array
    {
        $sale->load(['items.product', 'saleInstallments']);
        $customers = Customer::orderBy('name')->get();
        $products = Product::where('active', true)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('active', true)->orderBy('name')->get();

        return [
            'sale' => $sale,
            'customers' => $customers,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
        ];
    }

    public function getSalesReport(?string $startDate = null, ?string $endDate = null): array
    {
        return $this->salesReportQuery->getReport($startDate, $endDate);
    }

    public function getSaleForShow(Sale $sale): Sale
    {
        return $sale->load([
            'items.product',
            'saleInstallments',
            'customer',
            'paymentMethod',
            'user',
            'payments.paymentMethod',
            'cashShift.user:id,name',
        ]);
    }

    public function deleteSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            ($this->cancelSaleAction)($sale, auth()->id());
            $sale->delete();
        });
    }

    public function restoreSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            app(AccountingPeriodService::class)->lockForUpdate();
            $lockedSale = Sale::withTrashed()->where('id', $sale->id)->lockForUpdate()->firstOrFail();
            app(AccountingPeriodService::class)->assertOpen($lockedSale->created_at);

            $items = $lockedSale->items()->get();
            foreach ($items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();
                if ($product->stock < $item->quantity) {
                    throw new InsufficientStockException($product->name, $item->quantity, $product->stock);
                }
                $previousStock = (int) $product->stock;
                $newStock = $previousStock - $item->quantity;
                $product->stock = $newStock;
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovementType::Sale,
                    'quantity' => -$item->quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reference_type' => Sale::class,
                    'reference_id' => $lockedSale->id,
                    'reason' => "Restauração da venda #{$lockedSale->id}",
                    'user_id' => auth()->id(),
                ]);
            }

            $lockedSale->status = SaleStatus::Completed;
            $lockedSale->save();
            $lockedSale->restore();
        });
    }
}
