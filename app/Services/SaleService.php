<?php

namespace App\Services;

use App\Actions\CreateSaleAction;
use App\Actions\UpdateSaleAction;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;

class SaleService
{
    public function __construct(
        private CreateSaleAction $createSaleAction,
        private UpdateSaleAction $updateSaleAction
    ) {}

    public function createSale(array $data): Sale
    {
        $data['user_id'] = auth()->id();
        return ($this->createSaleAction)($data);
    }

    public function updateSale(Sale $sale, array $data): Sale
    {
        $data['user_id'] = auth()->id();
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

    public function getSaleForShow(Sale $sale): Sale
    {
        return $sale->load(['items.product', 'saleInstallments', 'customer', 'paymentMethod', 'user']);
    }

    public function deleteSale(Sale $sale): void
    {
        $sale->delete();
    }
}