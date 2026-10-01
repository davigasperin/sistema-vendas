<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        string $productName,
        int $requested,
        int $available
    ) {
        $message = "Estoque insuficiente para o produto: {$productName}. ";
        $message .= "Solicitado: {$requested}, Disponível: {$available}";

        parent::__construct($message);
    }
}