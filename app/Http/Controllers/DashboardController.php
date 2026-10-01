<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ExpenseService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private ExpenseService $expenseService
    ) {}

    public function index(): View
    {
        $salesStats = $this->dashboardService->getSalesStats();
        $latestSales = $this->dashboardService->getLatestSales(10);
        $paymentMethods = $this->dashboardService->getActivePaymentMethods();

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $financialSummary = $this->expenseService->getSummaryByPeriod($startOfMonth, $endOfMonth);
        $overdueExpenses = $this->expenseService->getOverdueExpenses()->take(5);
        $recentTransactions = $this->expenseService->getRecentTransactions(5);

        return view('dashboard', compact(
            'salesStats',
            'latestSales',
            'paymentMethods',
            'financialSummary',
            'overdueExpenses',
            'recentTransactions'
        ));
    }
}
