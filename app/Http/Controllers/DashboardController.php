<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ExpenseService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private ExpenseService $expenseService
    ) {}

    public function index(): Response
    {
        $salesStats = $this->dashboardService->getSalesStats();
        $latestSales = $this->dashboardService->getLatestSales(10);
        $paymentMethods = $this->dashboardService->getActivePaymentMethods();

        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $financialSummary = $this->expenseService->getSummaryByPeriod($startOfMonth, $endOfMonth);
        $overdueExpenses = $this->expenseService->getOverdueExpenses()->take(5);
        $recentTransactions = $this->expenseService->getRecentTransactions(5);

        return Inertia::render('Dashboard/Index', [
            'salesStats' => $salesStats,
            'latestSales' => $latestSales,
            'paymentMethods' => $paymentMethods,
            'financialSummary' => $financialSummary,
            'overdueExpenses' => $overdueExpenses,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
