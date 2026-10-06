<?php

namespace App\Actions;

use App\Models\SaleInstallment;
use App\Models\User;
use App\Services\AccountingPeriodService;
use DomainException;
use Illuminate\Support\Facades\DB;

class PaySaleInstallmentsAction
{
    public function __construct(
        private PaySaleInstallmentAction $paySaleInstallmentAction,
        private AccountingPeriodService $accountingPeriodService,
    ) {}

    public function __invoke(array $ids, User $user, int $paymentMethodId, ?string $paidDate = null): void
    {
        DB::transaction(function () use ($ids, $user, $paymentMethodId, $paidDate): void {
            $this->accountingPeriodService->lockForUpdate();

            $installments = SaleInstallment::with('sale')
                ->whereIn('id', array_unique($ids))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($installments->count() !== count(array_unique($ids))) {
                throw new DomainException('Uma ou mais parcelas não foram encontradas.');
            }

            foreach ($installments as $installment) {
                if (! $user->can('pay', $installment)) {
                    abort(403);
                }

                ($this->paySaleInstallmentAction)($installment, $user, $paymentMethodId, $paidDate);
            }
        });
    }
}
