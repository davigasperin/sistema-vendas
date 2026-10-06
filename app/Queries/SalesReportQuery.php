<?php

namespace App\Queries;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportQuery
{
    /**
     * @return array<string, mixed>
     */
    public function getReport(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? now()->startOfMonth()->toDateString();
        $endDate = $endDate ?? now()->endOfMonth()->toDateString();

        $startDateTime = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
        $endDateTime = Carbon::parse($endDate)->endOfDay()->toDateTimeString();

        $baseSalesQuery = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$startDateTime, $endDateTime]);

        $totalSales = (int) (clone $baseSalesQuery)->count();
        $netAmount = (float) (clone $baseSalesQuery)->sum('total_amount');
        $discountAmount = (float) (clone $baseSalesQuery)->sum('discount');
        $grossAmount = round($netAmount + $discountAmount, 2);
        $averageTicket = $totalSales > 0 ? round($netAmount / $totalSales, 2) : 0.0;

        $directPayments = DB::table('sale_payments')
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.created_at', [$startDateTime, $endDateTime])
            ->select(
                'payment_methods.id as method_id',
                'payment_methods.name as method_name',
                'sale_payments.amount as amount'
            );

        $fallbackSales = DB::table('sales')
            ->join('payment_methods', 'sales.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.created_at', [$startDateTime, $endDateTime])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sale_payments')
                    ->whereColumn('sale_payments.sale_id', 'sales.id');
            })
            ->select(
                'payment_methods.id as method_id',
                'payment_methods.name as method_name',
                'sales.total_amount as amount'
            );

        $allPayments = $directPayments->unionAll($fallbackSales);

        $paymentMethodsQuery = DB::query()
            ->fromSub($allPayments, 'combined_payments')
            ->select(
                'method_id as id',
                'method_name as name',
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('method_id', 'method_name')
            ->orderByDesc('total_amount')
            ->get();

        $totalPayments = (float) $paymentMethodsQuery->sum('total_amount');
        $paymentMethods = $paymentMethodsQuery->map(function ($pm) use ($totalPayments, $netAmount) {
            $total = (float) $pm->total_amount;
            $divisor = $totalPayments > 0 ? $totalPayments : $netAmount;

            return [
                'id' => (int) $pm->id,
                'name' => (string) $pm->name,
                'total_amount' => $total,
                'count' => (int) $pm->count,
                'percentage' => $divisor > 0 ? round(($total / $divisor) * 100, 1) : 0.0,
            ];
        })->values()->all();

        $topProductsQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.created_at', [$startDateTime, $endDateTime])
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        $topProducts = $topProductsQuery->map(function ($p) {
            $qty = (int) $p->total_quantity;
            $revenue = (float) $p->total_revenue;

            return [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'quantity' => $qty,
                'revenue' => $revenue,
                'average_price' => $qty > 0 ? round($revenue / $qty, 2) : 0.0,
            ];
        })->values()->all();

        $sellersQuery = DB::table('sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereNull('sales.deleted_at')
            ->whereBetween('sales.created_at', [$startDateTime, $endDateTime])
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.total_amount) as total_amount'),
                DB::raw('SUM(sales.discount) as total_discount'),
                DB::raw('AVG(sales.total_amount) as average_ticket')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_amount')
            ->get();

        $sellers = $sellersQuery->map(function ($s) {
            return [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'email' => (string) $s->email,
                'sales_count' => (int) $s->sales_count,
                'total_amount' => (float) $s->total_amount,
                'total_discount' => (float) $s->total_discount,
                'average_ticket' => round((float) $s->average_ticket, 2),
            ];
        })->values()->all();

        $dailySalesQuery = DB::table('sales')
            ->where('status', SaleStatus::Completed->value)
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->select(
                DB::raw('DATE(created_at) as sale_date'),
                DB::raw('COUNT(id) as sales_count'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('SUM(discount) as total_discount'),
                DB::raw('AVG(total_amount) as average_ticket')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('sale_date', 'asc')
            ->get();

        $dailySales = $dailySalesQuery->map(function ($d) {
            return [
                'date' => (string) $d->sale_date,
                'sales_count' => (int) $d->sales_count,
                'total_amount' => (float) $d->total_amount,
                'total_discount' => (float) $d->total_discount,
                'average_ticket' => round((float) $d->average_ticket, 2),
            ];
        })->values()->all();

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'totals' => [
                'total_sales' => $totalSales,
                'gross_amount' => $grossAmount,
                'discount_amount' => $discountAmount,
                'net_amount' => $netAmount,
                'average_ticket' => $averageTicket,
            ],
            'payment_methods' => $paymentMethods,
            'top_products' => $topProducts,
            'sellers' => $sellers,
            'daily_sales' => $dailySales,
        ];
    }
}
