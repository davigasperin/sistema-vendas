<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\V1\ExpenseResource;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseService $expenseService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Expense::with('category');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return ExpenseResource::collection($query->orderBy('due_date', 'desc')->paginate($perPage));
    }

    public function show(Expense $expense): ExpenseResource
    {
        $expense->load('category');

        return new ExpenseResource($expense);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $this->authorize('create', Expense::class);

        $expense = $this->expenseService->createExpense($request->validated());
        $expense->load('category');

        return (new ExpenseResource($expense))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function markPaid(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('markPaid', $expense);

        $paidDate = $request->filled('paid_date') ? (string) $request->input('paid_date') : null;
        $updated = $this->expenseService->markAsPaid($expense, $paidDate);
        $updated->load('category');

        return (new ExpenseResource($updated))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_OK);
    }
}
