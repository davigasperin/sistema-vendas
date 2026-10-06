<?php

namespace App\Http\Controllers;

use App\Actions\CloseMonthAction;
use App\Actions\PaySaleInstallmentAction;
use App\Actions\PaySaleInstallmentsAction;
use App\Enums\UserRole;
use App\Http\Requests\CloseMonthRequest;
use App\Http\Requests\PaySaleInstallmentRequest;
use App\Http\Requests\PaySaleInstallmentsRequest;
use App\Models\MonthClose;
use App\Models\PaymentMethod;
use App\Models\SaleInstallment;
use App\Queries\ReceivablesQuery;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceivablesController extends Controller
{
    public function __construct(
        private ReceivablesQuery $receivablesQuery,
        private PaySaleInstallmentAction $paySaleInstallmentAction,
        private PaySaleInstallmentsAction $paySaleInstallmentsAction,
        private CloseMonthAction $closeMonthAction,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $year = $request->filled('year') ? (int) $request->integer('year') : now()->year;
        $month = $request->filled('month') ? (int) $request->integer('month') : now()->month;

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            $year = now()->year;
            $month = now()->month;
        }

        $monthClose = MonthClose::query()
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return Inertia::render('Receivables/Index', [
            'installments' => $this->receivablesQuery->paginate($request, $user),
            'paymentMethods' => PaymentMethod::where('active', true)->orderBy('name')->get(),
            'customers' => $this->receivablesQuery->customers($user),
            'summary' => $this->receivablesQuery->summary($user),
            'monthSnapshot' => $user->can('close', MonthClose::class)
                ? $monthClose->totals ?? $this->receivablesQuery->monthSnapshot($year, $month)
                : null,
            'currentMonthClose' => $monthClose,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'filters' => $request->only(['status', 'customer_id', 'date_from', 'date_to']),
            'abilities' => [
                'canPay' => $user->isActive() && ($user->isAdmin() || in_array($user->role, [UserRole::Financial, UserRole::Seller], true)),
                'canCloseMonth' => $user->can('close', MonthClose::class),
            ],
        ]);
    }

    public function pay(PaySaleInstallmentRequest $request, SaleInstallment $saleInstallment): RedirectResponse
    {
        try {
            ($this->paySaleInstallmentAction)(
                $saleInstallment,
                $request->user(),
                (int) $request->validated('payment_method_id'),
                $request->validated('paid_date')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['installment' => $e->getMessage()]);
        }

        return back()->with('success', 'Parcela baixada com sucesso!');
    }

    public function payBatch(PaySaleInstallmentsRequest $request): RedirectResponse
    {
        try {
            ($this->paySaleInstallmentsAction)(
                $request->validated('installment_ids'),
                $request->user(),
                (int) $request->validated('payment_method_id'),
                $request->validated('paid_date')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['installment' => $e->getMessage()]);
        }

        return back()->with('success', 'Parcelas baixadas com sucesso!');
    }

    public function closeMonth(CloseMonthRequest $request, int $year, int $month): RedirectResponse
    {
        try {
            ($this->closeMonthAction)((int) $request->validated('year'), (int) $request->validated('month'), $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['month_close' => $e->getMessage()]);
        }

        return back()->with('success', 'Mês fechado com sucesso!');
    }
}
