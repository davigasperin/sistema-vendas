<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case SaleCancel = 'sale_cancel';
    case ManualAdjustment = 'manual_adjustment';
    case InitialStock = 'initial_stock';
    case Correction = 'correction';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Venda',
            self::SaleCancel => 'Cancelamento de Venda',
            self::ManualAdjustment => 'Ajuste Manual',
            self::InitialStock => 'Estoque Inicial',
            self::Correction => 'Correção de Inventário',
        };
    }

    public function isPositive(): bool
    {
        return match ($this) {
            self::SaleCancel, self::InitialStock => true,
            self::Sale => false,
            self::ManualAdjustment, self::Correction => false,
        };
    }
}
