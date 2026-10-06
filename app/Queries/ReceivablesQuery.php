<?php

namespace App\Queries;

use App\Enums\ExpenseStatus;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReceivablesQuery
{
    public function paginate(Request $request, User $user): LengthAwarePaginator
    {
        $query = SaleInstallment::query()
            ->whereHas('sale', fn (Builder $sale) => $sale->where('status', SaleStatus::Completed))
            ->with(['sale.customer:id,name', 'sale.user:id,name', 'paymentMethod:id,name'])
            ->orderBy('due_date')
            ->orderBy('id');

        $this->applyOwnership($query, $user);

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();

            match ($status) {
                'pending' => $query->where('is_paid', false)
                    ->whereDate('due_date', '>=', now()->toDateString()),
                'overdue' => $query->where('is_paid', false)
                    ->whereDate('due_date', '<', now()->toDateString()),
                'paid' => $query->where('is_paid', true),
                default => null,
            };
        }

        if ($request->filled('customer_id')) {
            $query->whereHas('sale', fn ($sale) => $sale->where('customer_id', (int) $request->input('customer_id')));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('due_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('due_date', '<=', $request->input('date_to'));
        }

        return $query->paginate(15)->withQueryString();
    }

    public function customers(User $user)
    {
        return Customer::query()
            ->whereHas('sales.saleInstallments', function (Builder $query) use ($user): void {
                if (! $user->isAdmin() && $user->role !== UserRole::Financial) {
                    $query->whereHas('sale', fn (Builder $sale) => $sale->where('user_id', $user->id));
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function summary(User $user): array
    {
        $base = SaleInstallment::query()
            ->whereHas('sale', fn (Builder $sale) => $sale->where('status', SaleStatus::Completed));
        $this->applyOwnership($base, $user);

        $open = (clone $base)->where('is_paid', false);

        return [
            'a_receber' => $this->toMoney($open->sum('amount')),
            'vencido' => $this->toMoney(
                (clone $open)->whereDate('due_date', '<', now()->toDateString())->sum('amount')
            ),
            'recebido_mes' => $this->toMoney(
                (clone $base)
                    ->where('is_paid', true)
                    ->whereBetween('paid_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                    ->sum('amount')
            ),
        ];
    }

    public function monthSnapshot(int $year, int $month): array
    {
        $start = now()->setDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $salesCompleted = Sale::where('status', SaleStatus::Completed)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $spotSales = Sale::query()
            ->where('status', SaleStatus::Completed)
            ->where('installments', '<=', 1)
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $installmentsReceived = SaleInstallment::where('is_paid', true)
            ->whereBetween('paid_date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('sale', fn ($sale) => $sale->where('installments', '>', 1)->where('status', SaleStatus::Completed))
            ->sum('amount');

        $expensesPaid = Expense::query()
            ->expenses()
            ->where('status', ExpenseStatus::Paid)
            ->whereBetween('paid_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $installmentsReceivedMoney = $this->toMoney($installmentsReceived);
        $spotSalesMoney = $this->toMoney($spotSales);

        return [
            'year' => $year,
            'month' => $month,
            'sales_completed_total' => $this->toMoney($salesCompleted),
            'installments_received_total' => $installmentsReceivedMoney,
            'spot_sales_total' => $spotSalesMoney,
            'expenses_paid_total' => $this->toMoney($expensesPaid),
            'cash_balance' => round($installmentsReceivedMoney + $spotSalesMoney - $this->toMoney($expensesPaid), 2),
        ];
    }

    private function applyOwnership(Builder $query, User $user): void
    {
        if ($user->isAdmin() || $user->role === UserRole::Financial) {
            return;
        }

        $query->whereHas('sale', fn ($sale) => $sale->where('user_id', $user->id));
    }

    private function toMoney(mixed $amount): float
    {
        return round((float) $amount, 2);
    }
}
