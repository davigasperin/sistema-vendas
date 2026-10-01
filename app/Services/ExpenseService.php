<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;

class ExpenseService
{
    public function getExpensesPaginated(?string $search = null, ?string $status = null, ?string $type = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Expense::query()->with('category');

        if (! empty($search)) {
            $query->where('description', 'like', '%'.$search.'%');
        }

        if (! is_null($status)) {
            $query->where('status', $status);
        }

        if (! is_null($type)) {
            $query->where('type', $type);
        }

        return $query->orderBy('due_date', 'desc')->paginate($perPage);
    }

    public function getAllCategories(): Collection
    {
        return ExpenseCategory::orderBy('name')->get();
    }

    public function getCategoriesByType(string $type): Collection
    {
        return ExpenseCategory::where('type', $type)->orderBy('name')->get();
    }

    public function createExpense(array $data): Expense
    {
        $this->checkOverdue();

        $data['status'] = $data['status'] ?? Expense::STATUS_PENDING;

        if ($data['status'] === Expense::STATUS_PAID && empty($data['paid_date'])) {
            $data['paid_date'] = now()->toDateString();
        }

        return Expense::create($data);
    }

    public function updateExpense(Expense $expense, array $data): Expense
    {
        if (isset($data['status']) && $data['status'] === Expense::STATUS_PAID && empty($data['paid_date'])) {
            $data['paid_date'] = now()->toDateString();
        }

        $expense->update($data);

        if ($expense->isPending() && $expense->due_date < now()->toDateString()) {
            $expense->update(['status' => Expense::STATUS_OVERDUE]);
        }

        return $expense->fresh();
    }

    public function markAsPaid(Expense $expense, ?string $paidDate = null): Expense
    {
        $expense->update([
            'status' => Expense::STATUS_PAID,
            'paid_date' => $paidDate ?? now()->toDateString(),
        ]);

        return $expense->fresh();
    }

    public function markAsPending(Expense $expense): Expense
    {
        $expense->update([
            'status' => Expense::STATUS_PENDING,
            'paid_date' => null,
        ]);

        return $expense->fresh();
    }

    public function markAsCancelled(Expense $expense): Expense
    {
        $expense->update(['status' => Expense::STATUS_CANCELLED]);

        return $expense->fresh();
    }

    public function checkOverdue(): void
    {
        Expense::pending()
            ->where('due_date', '<', now()->toDateString())
            ->update(['status' => Expense::STATUS_OVERDUE]);
    }

    public function isPaid(Expense $expense): bool
    {
        return $expense->isPaid();
    }

    public function isOverdue(Expense $expense): bool
    {
        return $expense->isOverdue() ||
            ($expense->isPending() && $expense->due_date < now()->toDateString());
    }

    public function deleteExpense(Expense $expense): void
    {
        $expense->delete();
    }

    public function getTotalByType(string $type, ?string $startDate = null, ?string $endDate = null): float
    {
        $query = Expense::where('type', $type)->where('status', Expense::STATUS_PAID);

        if ($startDate && $endDate) {
            $query->whereBetween('paid_date', [$startDate, $endDate]);
        }

        return (float) $query->sum('amount');
    }

    public function getTotalPendingByType(string $type, ?string $endDate = null): float
    {
        $query = Expense::where('type', $type)
            ->whereIn('status', [Expense::STATUS_PENDING, Expense::STATUS_OVERDUE]);

        if ($endDate) {
            $query->where('due_date', '<=', $endDate);
        }

        return (float) $query->sum('amount');
    }

    public function getBalance(?string $startDate = null, ?string $endDate = null): float
    {
        $salesIncome = $this->getSalesIncome($startDate, $endDate);
        $manualIncome = $this->getTotalByType(Expense::TYPE_INCOME, $startDate, $endDate);
        $expenses = $this->getTotalByType(Expense::TYPE_EXPENSE, $startDate, $endDate);

        return ($salesIncome + $manualIncome) - $expenses;
    }

    public function getSalesIncome(?string $startDate = null, ?string $endDate = null): float
    {
        $query = Sale::whereNotNull('id');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return (float) $query->sum('total_amount');
    }

    public function getOverdueExpenses(): Collection
    {
        $this->checkOverdue();

        return Expense::with('category')
            ->whereIn('status', [Expense::STATUS_PENDING, Expense::STATUS_OVERDUE])
            ->where('due_date', '<', now()->toDateString())
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getRecentTransactions(int $limit = 5): SupportCollection
    {
        $sales = Sale::with('customer')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($sale) {
                return (object) [
                    'type' => 'income',
                    'description' => 'Venda #'.$sale->id.($sale->customer?->name ? ' - '.$sale->customer->name : ''),
                    'amount' => $sale->total_amount,
                    'date' => $sale->created_at,
                    'status' => 'paid',
                ];
            });

        $expenses = Expense::with('category')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($expense) {
                return (object) [
                    'type' => 'expense',
                    'description' => $expense->description,
                    'amount' => $expense->amount,
                    'date' => $expense->paid_date ?? $expense->due_date,
                    'status' => $expense->status,
                ];
            });

        return $sales->concat($expenses)->sortByDesc('date')->take($limit);
    }

    public function getExpensesByCategory(int $categoryId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = Expense::where('category_id', $categoryId);

        if ($startDate && $endDate) {
            $query->whereBetween('due_date', [$startDate, $endDate]);
        }

        return $query->orderBy('due_date', 'desc')->get();
    }

    public function getSummaryByPeriod(?string $startDate = null, ?string $endDate = null): array
    {
        if (! $startDate) {
            $startDate = now()->startOfMonth()->toDateString();
        }
        if (! $endDate) {
            $endDate = now()->endOfMonth()->toDateString();
        }

        $salesIncome = $this->getSalesIncome($startDate, $endDate);
        $manualIncome = $this->getTotalByType(Expense::TYPE_INCOME, $startDate, $endDate);
        $expenses = $this->getTotalByType(Expense::TYPE_EXPENSE, $startDate, $endDate);

        $pendingExpenses = $this->getTotalPendingByType(Expense::TYPE_EXPENSE, $endDate);
        $overdueCount = $this->getOverdueExpenses()->count();

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'income' => [
                'sales' => $salesIncome,
                'manual' => $manualIncome,
                'total' => $salesIncome + $manualIncome,
            ],
            'expenses' => [
                'paid' => $expenses,
                'pending' => $pendingExpenses,
            ],
            'balance' => ($salesIncome + $manualIncome) - $expenses,
            'overdue_count' => $overdueCount,
        ];
    }
}
