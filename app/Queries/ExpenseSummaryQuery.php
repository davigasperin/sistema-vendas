<?php

namespace App\Queries;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\Sale;

class ExpenseSummaryQuery
{
    /**
     * @return array<string, mixed>
     */
    public function getSummaryByPeriod(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        $salesIncome = (float) Sale::where('status', SaleStatus::Completed)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->sum('total_amount');

        $manualIncome = (float) Expense::where('type', ExpenseType::Income)
            ->where('status', ExpenseStatus::Paid)
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->sum('amount');

        $paidExpenses = (float) Expense::where('type', ExpenseType::Expense)
            ->where('status', ExpenseStatus::Paid)
            ->whereBetween('paid_date', [$startDate, $endDate])
            ->sum('amount');

        $pendingExpenses = (float) Expense::where('type', ExpenseType::Expense)
            ->whereIn('status', [ExpenseStatus::Pending, ExpenseStatus::Overdue])
            ->where('due_date', '<=', $endDate)
            ->sum('amount');

        $overdueCount = Expense::whereIn('status', [ExpenseStatus::Pending, ExpenseStatus::Overdue])
            ->where('due_date', '<', today()->toDateString())
            ->count();

        $totalIncome = $salesIncome + $manualIncome;
        $balance = $totalIncome - $paidExpenses;

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'income' => [
                'sales' => $salesIncome,
                'manual' => $manualIncome,
                'total' => $totalIncome,
            ],
            'expenses' => [
                'paid' => $paidExpenses,
                'pending' => $pendingExpenses,
            ],
            'balance' => $balance,
            'overdue_count' => $overdueCount,
        ];
    }
}
