<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Queries\DashboardMetricsQuery;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private DashboardMetricsQuery $metricsQuery
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSalesStats(): array
    {
        return $this->metricsQuery->getSalesStats();
    }

    /**
     * @return Collection<int, Sale>
     */
    public function getLatestSales(int $limit = 10): Collection
    {
        return Sale::with(['customer', 'paymentMethod'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, PaymentMethod>
     */
    public function getActivePaymentMethods(): Collection
    {
        return PaymentMethod::where('active', true)->orderBy('name')->get();
    }
}
