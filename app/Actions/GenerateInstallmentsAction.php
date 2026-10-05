<?php

namespace App\Actions;

use App\Models\Sale;
use App\Models\SaleInstallment;

class GenerateInstallmentsAction
{
    /**
     * @param  list<float>  $amounts
     * @param  list<string>  $dueDates
     */
    public function __invoke(Sale $sale, array $amounts = [], array $dueDates = [], int $installmentCount = 1): void
    {
        if ($installmentCount < 1) {
            $installmentCount = 1;
        }

        $totalCents = (int) round((float) $sale->total_amount * 100);
        $baseCents = intdiv($totalCents, $installmentCount);
        $remainderCents = $totalCents % $installmentCount;

        $hasCustomAmounts = count($amounts) === $installmentCount;
        $today = now();

        for ($i = 0; $i < $installmentCount; $i++) {
            if ($hasCustomAmounts) {
                $amount = round((float) $amounts[$i], 2);
            } else {
                $cents = $baseCents + ($i < $remainderCents ? 1 : 0);
                $amount = round($cents / 100, 2);
            }

            $dueDate = ! empty($dueDates[$i])
                ? $dueDates[$i]
                : $today->copy()->addMonths($i + 1)->format('Y-m-d');

            SaleInstallment::create([
                'sale_id' => $sale->id,
                'installment_number' => $i + 1,
                'amount' => $amount,
                'due_date' => $dueDate,
                'is_paid' => false,
                'payment_method_id' => $sale->payment_method_id,
            ]);
        }
    }
}
