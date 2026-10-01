<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenseService) {}

    public function index(Request $request): View
    {
        $expenses = $this->expenseService->getExpensesPaginated(
            $request->filled('search') ? $request->search : null,
            $request->filled('status') ? $request->status : null,
            $request->filled('type') ? $request->type : null
        );

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => $this->expenseService->getAllCategories(),
        ]);
    }

    public function create(): View
    {
        return view('expenses.create', [
            'categories' => $this->expenseService->getCategoriesByType(Expense::TYPE_EXPENSE),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenseService->createExpense($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Despesa cadastrada com sucesso!');
    }

    public function show(Expense $expense): View
    {
        $expense->load('category');

        return view('expenses.show', [
            'expense' => $expense,
        ]);
    }

    public function edit(Expense $expense): View
    {
        $expense->load('category');

        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => $this->expenseService->getAllCategories(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenseService->updateExpense($expense, $request->validated());

        return redirect()->route('expenses.index')->with('success', 'Despesa atualizada com sucesso!');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->expenseService->deleteExpense($expense);

        return redirect()->route('expenses.index')->with('success', 'Despesa excluída com sucesso!');
    }

    public function markPaid(Request $request, Expense $expense): JsonResponse
    {
        $paidDate = $request->filled('paid_date') ? $request->paid_date : null;
        $this->expenseService->markAsPaid($expense, $paidDate);

        return response()->json([
            'success' => true,
            'message' => 'Despesa marcada como paga!',
            'expense' => $expense->fresh(),
        ]);
    }

    public function report(Request $request): View
    {
        $startDate = $request->filled('start_date') ? $request->start_date : now()->startOfMonth()->toDateString();
        $endDate = $request->filled('end_date') ? $request->end_date : now()->endOfMonth()->toDateString();

        $summary = $this->expenseService->getSummaryByPeriod($startDate, $endDate);

        return view('expenses.report', [
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'overdueExpenses' => $this->expenseService->getOverdueExpenses(),
        ]);
    }
}
