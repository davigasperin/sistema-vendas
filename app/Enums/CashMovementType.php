<?php

namespace App\Enums;

enum CashMovementType: string
{
    case Supply = 'supply';
    case Bleed = 'bleed';
    case Receipt = 'receipt';

    public function label(): string
    {
        return match ($this) {
            self::Supply => 'Suprimento (Entrada)',
            self::Bleed => 'Sangria (Retirada)',
            self::Receipt => 'Recebimento de parcela',
        };
    }

    public function isAddition(): bool
    {
        return in_array($this, [self::Supply, self::Receipt], true);
    }
}
