<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Sale;
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService)
    {
        $this->authorizeResource(Sale::class, 'sale');
    }

    public function index(Request $request)
    {
        $data = $this->saleService->getSalesFiltered($request);

        return view('sales.index', $data);
    }

    public function create()
    {
        $data = $this->saleService->getSalesForCreate();

        return view('sales.create', $data);
    }

    public function store(SaleRequest $request)
    {
        $this->saleService->createSale($request->validated());

        return redirect()->route('sales.index')->with('success', 'Venda registrada com sucesso!');
    }

    public function show(Sale $sale)
    {
        $sale = $this->saleService->getSaleForShow($sale);

        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $data = $this->saleService->getSalesForEdit($sale);

        return view('sales.edit', $data);
    }

    public function update(SaleRequest $request, Sale $sale)
    {
        $this->saleService->updateSale($sale, $request->validated());

        return redirect()->route('sales.index')->with('success', 'Venda atualizada com sucesso!');
    }

    public function destroy(Sale $sale)
    {
        $this->saleService->deleteSale($sale);

        return redirect()->route('sales.index')->with('success', 'Venda excluída com sucesso!');
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
