<?php

namespace App\Enums;

enum CashMovementType: string
{
    case Supply = 'supply';
    case Bleed = 'bleed';

    public function label(): string
    {
        return match ($this) {
            self::Supply => 'Suprimento (Entrada)',
            self::Bleed => 'Sangria (Retirada)',
        };
    }

    public function isAddition(): bool
    {
        return $this === self::Supply;
    }
}
