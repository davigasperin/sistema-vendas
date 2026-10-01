<?php

namespace App\Enums;

enum ExpenseType: string
{
    case Expense = 'expense';
    case Income = 'income';

    public function label(): string
    {
        return match ($this) {
            self::Expense => 'Despesa',
            self::Income => 'Receita',
        };
    }
}
