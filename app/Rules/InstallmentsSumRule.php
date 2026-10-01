<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class InstallmentsSumRule implements ValidationRule
{
    protected float $saleTotal;

    public function __construct(float $saleTotal)
    {
        $this->saleTotal = $saleTotal;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $amounts = request()->input('installment_amounts', []);
        $total = array_sum(array_map('floatval', $amounts));
        $diff = abs($this->saleTotal - $total);

        if ($diff > 0.05) {
            $fail('A soma das parcelas deve ser igual ao total da venda.');
        }
    }
}
