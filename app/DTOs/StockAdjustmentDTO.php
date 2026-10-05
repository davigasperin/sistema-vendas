<?php

namespace App\DTOs;

use App\Enums\StockMovementType;

readonly class StockAdjustmentDTO
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public StockMovementType $type,
        public ?string $reason = null,
        public ?int $userId = null,
    ) {}
}
