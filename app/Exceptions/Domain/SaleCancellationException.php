<?php

namespace App\Exceptions\Domain;

use Exception;

class SaleCancellationException extends Exception
{
    public static function hasPaidInstallments(int $saleId): self
    {
        return new self("A venda #{$saleId} possui parcelas já quitadas e não pode ser cancelada ou excluída sem estorno prévio.");
    }

    public static function alreadyCancelled(int $saleId): self
    {
        return new self("A venda #{$saleId} já está cancelada.");
    }
}
