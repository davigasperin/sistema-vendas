<?php

namespace App\Actions;

use App\Enums\CashMovementType;
use App\Enums\CashShiftStatus;
use App\Enums\SaleStatus;
use App\Models\CashShift;
use App\Services\AccountingPeriodService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CloseCashShiftAction
{
    public function __construct(private AccountingPeriodService $accountingPeriodService) {}

    public function __invoke(CashShift $shift, float $reportedAmount, ?string $notes = null): CashShift
    {
        if ($reportedAmount < 0) {
            throw new DomainException('O valor informado no fechamento não pode ser negativo.');
        }

        return DB::transaction(function () use ($shift, $reportedAmount, $notes): CashShift {
            $this->accountingPeriodService->lockForUpdate();
            $shift = CashShift::query()->lockForUpdate()->findOrFail($shift->id);

            if (! $shift->isOpen()) {
                throw new DomainException('Este caixa já se encontra fechado.');
            }

            $initialCents = $this->toCents((string) $shift->initial_amount);
            $suppliesCents = $this->toCents((string) $shift->movements()
                ->where('type', CashMovementType::Supply)
                ->sum('amount'));
            $bleedsCents = $this->toCents((string) $shift->movements()
                ->where('type', CashMovementType::Bleed)
                ->sum('amount'));
            $receiptsCents = $this->toCents((string) $shift->movements()
                ->where('type', CashMovementType::Receipt)
                ->sum('amount'));

            $sales = $shift->sales()
                ->where('status', SaleStatus::Completed)
                ->with(['payments.paymentMethod', 'paymentMethod', 'saleInstallments:id,sale_id,is_paid'])
                ->get();
            $cashSalesCents = 0;

            foreach ($sales as $sale) {
                if ($sale->installments > 1 || $sale->saleInstallments->contains('is_paid', true)) {
                    continue;
                }

                if ($sale->payments->isNotEmpty()) {
                    foreach ($sale->payments as $payment) {
                        $methodName = $payment->paymentMethod !== null ? $payment->paymentMethod->name : '';
                        if (mb_strtolower($methodName) === 'dinheiro') {
                            $cashSalesCents += $this->toCents((string) $payment->amount);
                        }
                    }
                } else {
                    $methodName = $sale->paymentMethod !== null ? $sale->paymentMethod->name : '';
                    if (mb_strtolower($methodName) === 'dinheiro') {
                        $cashSalesCents += $this->toCents((string) $sale->total_amount);
                    }
                }
            }

            $expectedCents = $initialCents + $suppliesCents + $receiptsCents - $bleedsCents + $cashSalesCents;
            $reportedCents = $this->toCents((string) $reportedAmount);
            $expectedAmount = number_format($expectedCents / 100, 2, '.', '');
            $difference = number_format(($reportedCents - $expectedCents) / 100, 2, '.', '');

            $mergedNotes = $shift->notes;
            if ($notes && trim($notes) !== '') {
                $mergedNotes = $mergedNotes ? $mergedNotes."\n".trim($notes) : trim($notes);
            }

            $shift->update([
                'closed_at' => now(),
                'final_amount_reported' => number_format($reportedCents / 100, 2, '.', ''),
                'final_amount_expected' => $expectedAmount,
                'difference' => $difference,
                'status' => CashShiftStatus::Closed,
                'notes' => $mergedNotes,
            ]);

            return $shift->fresh();
        });
    }

    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
