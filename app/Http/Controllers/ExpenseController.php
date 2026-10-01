<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(private ExpenseService $expenseService)
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request): Response
    {
        $expenses = $this->expenseService->getExpensesPaginated(
            $request->filled('search') ? $request->search : null,
            $request->filled('status') ? $request->status : null,
            $request->filled('type') ? $request->type : null
        );

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $this->expenseService->getAllCategories(),
            'filters' => $request->only(['search', 'status', 'type']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Create', [
            'categories' => $this->expenseService->getAllCategories(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenseService->createExpense($request->validated());

        return redirect()->route('expenses.index')->with('success', 'Despesa cadastrada com sucesso!');
    }

    public function show(Expense $expense): Response
    {
        $expense->load('category');

        return Inertia::render('Expenses/Show', [
            'expense' => $expense,
        ]);
    }

    public function edit(Expense $expense): Response
    {
        $expense->load('category');

        return Inertia::render('Expenses/Edit', [
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

    public function markPaid(Request $request, Expense $expense)
    {
        $paidDate = $request->filled('paid_date') ? $request->paid_date : null;
        $this->expenseService->markAsPaid($expense, $paidDate);

        return back()->with('success', 'Despesa marcada como paga!');
    }

    public function report(Request $request): Response
    {
        $startDate = $request->filled('start_date') ? $request->start_date : now()->startOfMonth()->toDateString();
        $endDate = $request->filled('end_date') ? $request->end_date : now()->endOfMonth()->toDateString();

        $summary = $this->expenseService->getSummaryByPeriod($startDate, $endDate);

        return Inertia::render('Expenses/Report', [
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'overdueExpenses' => $this->expenseService->getOverdueExpenses(),
        ]);
    }
}
