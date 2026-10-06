<?php

namespace App\Queries;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleInstallment;

class DashboardMetricsQuery
{
    /**
     * @return array<string, mixed>
     */
    public function getSalesStats(): array
    {
        $completedSales = Sale::where('status', SaleStatus::Completed);

        $salesToday = (clone $completedSales)->whereDate('created_at', today())->count();

        $monthSalesQuery = (clone $completedSales)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month);

        $salesThisMonth = (float) $monthSalesQuery->sum('total_amount');
        $averageThisMonth = (float) ($monthSalesQuery->avg('total_amount') ?? 0.0);

        $totalSales = (clone $completedSales)->count();
        $averageSale = (float) ((clone $completedSales)->avg('total_amount') ?? 0.0);

        $salesThisWeek = (clone $completedSales)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $lowStockCount = Product::where('active', true)
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->count();

        $pendingInstallments = SaleInstallment::query()
            ->whereHas('sale', fn ($sale) => $sale->where('status', SaleStatus::Completed));

        $pendingInstallmentsCount = (clone $pendingInstallments)->where('is_paid', false)->count();
        $pendingInstallmentsAmount = $this->toMoney(
            (clone $pendingInstallments)->where('is_paid', false)->sum('amount')
        );

        $overdueInstallmentsQuery = (clone $pendingInstallments)
            ->where('is_paid', false)
            ->where('due_date', '<', today()->toDateString());

        $overdueInstallmentsCount = (clone $overdueInstallmentsQuery)->count();
        $overdueInstallmentsAmount = $this->toMoney((clone $overdueInstallmentsQuery)->sum('amount'));

        return [
            'today' => $salesToday,
            'month' => $salesThisMonth,
            'total' => $totalSales,
            'average' => $averageSale,
            'week' => $salesThisWeek,
            'averageThisMonth' => $averageThisMonth,
            'lowStockCount' => $lowStockCount,
            'pendingInstallmentsCount' => $pendingInstallmentsCount,
            'pendingInstallmentsAmount' => $pendingInstallmentsAmount,
            'overdueInstallmentsCount' => $overdueInstallmentsCount,
            'overdueInstallmentsAmount' => $overdueInstallmentsAmount,
        ];
    }

    private function toMoney(mixed $amount): float
    {
        return round((float) $amount, 2);
    }
}
