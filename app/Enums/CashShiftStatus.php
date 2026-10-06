<?php

namespace App\Enums;

enum CashShiftStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberto',
            self::Closed => 'Fechado',
        };
    }
}
