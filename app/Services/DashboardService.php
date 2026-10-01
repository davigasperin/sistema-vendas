<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Sale;
use Illuminate\Support\Collection;

class DashboardService
{
    public function getSalesStats(): array
    {
        $salesToday = Sale::whereDate('created_at', today())->count();
        $salesThisMonth = Sale::whereMonth('created_at', now()->month)->sum('total_amount');
        $totalSales = Sale::count();
        $averageSale = Sale::avg('total_amount') ?? 0;
        $salesThisWeek = Sale::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $averageThisMonth = Sale::whereMonth('created_at', now()->month)->avg('total_amount') ?? 0;

        return [
            'today' => $salesToday,
            'month' => $salesThisMonth,
            'total' => $totalSales,
            'average' => $averageSale,
            'week' => $salesThisWeek,
            'averageThisMonth' => $averageThisMonth,
        ];
    }

    public function getLatestSales(int $limit = 10): Collection
    {
        return Sale::with(['customer', 'paymentMethod'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getActivePaymentMethods(): Collection
    {
        return PaymentMethod::where('active', true)->orderBy('name')->get();
    }
}
