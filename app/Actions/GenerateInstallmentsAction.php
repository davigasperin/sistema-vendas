<?php

namespace App\Actions;

use App\Models\Sale;
use App\Models\SaleInstallment;

class GenerateInstallmentsAction
{
    public function __invoke(Sale $sale, array $amounts, array $dueDates, int $installmentCount = 1): void
    {
        $totalAmount = $sale->total_amount;
        $defaultAmount = round($totalAmount / $installmentCount, 2);
        $today = now();

        for ($i = 0; $i < $installmentCount; $i++) {
            $amount = $amounts[$i] ?? $defaultAmount;
            $dueDate = $dueDates[$i] ?? $today->copy()->addMonths($i)->format('Y-m-d');

            SaleInstallment::create([
                'sale_id' => $sale->id,
                'installment_number' => $i + 1,
                'amount' => $amount,
                'due_date' => $dueDate,
            ]);
        }
    }
}