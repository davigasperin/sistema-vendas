<?php

namespace App\Actions;

use App\Enums\SaleStatus;
use App\Models\MonthClose;
use App\Models\PaymentMethod;
use App\Models\SaleInstallment;
use App\Models\User;
use App\Services\AccountingPeriodService;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaySaleInstallmentAction
{
    public function __construct(
        private AddCashMovementAction $addCashMovementAction,
        private AccountingPeriodService $accountingPeriodService,
    ) {}

    public function __invoke(
        SaleInstallment $installment,
        User $user,
        ?int $paymentMethodId = null,
        ?string $paidDate = null
    ): SaleInstallment {
        return DB::transaction(function () use ($installment, $user, $paymentMethodId, $paidDate): SaleInstallment {
            $this->accountingPeriodService->lockForUpdate();

            $locked = SaleInstallment::query()->with('sale')->lockForUpdate()->findOrFail($installment->id);
            $sale = $locked->sale;

            if (! $sale || $sale->trashed() || $sale->status === SaleStatus::Cancelled) {
                throw new DomainException('Não é possível baixar parcela de venda cancelada ou excluída.');
            }

            if ($locked->is_paid) {
                throw new DomainException('Esta parcela já está paga.');
            }

            $methodId = $paymentMethodId ?? $locked->payment_method_id;
            if (! $methodId) {
                throw new DomainException('Informe o método de pagamento.');
            }

            $method = PaymentMethod::query()->find($methodId);
            if (! $method || ! $method->active) {
                throw new DomainException('Método de pagamento inativo ou inexistente.');
            }

            $date = Carbon::parse($paidDate ?? now())->toDateString();
            $closed = MonthClose::query()
                ->where('year', Carbon::parse($date)->year)
                ->where('month', Carbon::parse($date)->month)
                ->exists();

            if ($closed) {
                throw new DomainException('O mês de referência do recebimento já está fechado.');
            }

            $shift = null;
            if (mb_strtolower($method->name) === 'dinheiro') {
                $shift = $user->currentCashShift();

                if (! $shift) {
                    throw new DomainException('Não há caixa aberto para receber pagamento em dinheiro.');
                }
            }

            $locked->markAsPaid($date, (int) $method->id);

            if ($shift) {
                $this->addCashMovementAction->addReceipt(
                    $shift,
                    $user->id,
                    (float) $locked->amount,
                    "Recebimento da parcela {$locked->installment_number} da venda #{$locked->sale_id}"
                );
            }

            return $locked->fresh(['sale', 'paymentMethod']);
        });
    }
}
