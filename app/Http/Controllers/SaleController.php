<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService)
    {
        $this->authorizeResource(Sale::class, 'sale');
    }

    public function index(Request $request): Response
    {
        $data = $this->saleService->getSalesFiltered($request);

        return Inertia::render('Sales/Index', $data + [
            'filters' => $request->only([
                'customer_id', 'payment_method_id', 'date_from', 'date_to', 'trashed',
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $data = $this->saleService->getSalesForCreate();

        return Inertia::render('Sales/Create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'products' => Product::where('active', true)
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->get(['id', 'name', 'price', 'stock']),
            'paymentMethods' => $data['paymentMethods'],
            'currentShift' => $request->user()?->currentCashShift(),
        ]);
    }

    public function store(SaleRequest $request)
    {
        $this->saleService->createSale($request->validated());

        return redirect()->route('sales.index')->with('success', 'Venda registrada com sucesso!');
    }

    public function show(Sale $sale): Response
    {
        $sale = $this->saleService->getSaleForShow($sale);

        return Inertia::render('Sales/Show', compact('sale'));
    }

    public function edit(Sale $sale): Response
    {
        $data = $this->saleService->getSalesForEdit($sale);

        return Inertia::render('Sales/Edit', $data);
    }

    public function update(SaleRequest $request, Sale $sale)
    {
        $this->saleService->updateSale($sale, $request->validated());

        return redirect()->route('sales.index')->with('success', 'Venda atualizada com sucesso!');
    }

    public function destroy(Sale $sale)
    {
        $this->saleService->deleteSale($sale);

        return redirect()->route('sales.index')->with('success', 'Venda cancelada e excluída com sucesso!');
    }

    public function restore(Sale $sale)
    {
        $this->authorize('restore', $sale);
        $this->saleService->restoreSale($sale);

        return redirect()->route('sales.index')->with('success', 'Venda restaurada com sucesso!');
    }

    public function downloadPdf(Sale $sale)
    {
        $sale = $this->saleService->getSaleForShow($sale);
        $pdf = Pdf::loadView('sales.pdf', compact('sale'));

        return $pdf->download("venda_{$sale->id}.pdf");
    }
}
