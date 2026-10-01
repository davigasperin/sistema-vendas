<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelSaleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Http\Resources\V1\SaleResource;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $saleService,
        private CancelSaleAction $cancelSaleAction
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Sale::with(['customer', 'paymentMethod', 'user', 'items.product', 'saleInstallments']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return SaleResource::collection($query->latest()->paginate($perPage));
    }

    public function show(Sale $sale): SaleResource
    {
        $sale->load(['customer', 'paymentMethod', 'user', 'items.product', 'saleInstallments']);

        return new SaleResource($sale);
    }

    public function store(SaleRequest $request): JsonResponse
    {
        $this->authorize('create', Sale::class);

        $sale = $this->saleService->createSale($request->validated());
        $sale->load(['customer', 'paymentMethod', 'user', 'items.product', 'saleInstallments']);

        return (new SaleResource($sale))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function cancel(Request $request, Sale $sale): JsonResponse
    {
        $this->authorize('delete', $sale);

        $cancelledSale = ($this->cancelSaleAction)($sale, $request->user()?->id);
        $cancelledSale->load(['customer', 'paymentMethod', 'user', 'items.product', 'saleInstallments']);

        return (new SaleResource($cancelledSale))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_OK);
    }
}
