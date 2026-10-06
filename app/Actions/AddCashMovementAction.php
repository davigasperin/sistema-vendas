<?php

namespace App\Actions;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashShift;
use DomainException;
use Illuminate\Support\Facades\DB;

class AddCashMovementAction
{
    public function __invoke(CashShift $shift, int $userId, CashMovementType $type, float $amount, string $reason): CashMovement
    {
        if ($type === CashMovementType::Receipt) {
            throw new DomainException('Recebimentos só podem ser registrados pela baixa de parcelas.');
        }

        return $this->create($shift, $userId, $type, $amount, $reason);
    }

    public function addReceipt(CashShift $shift, int $userId, float $amount, string $reason): CashMovement
    {
        return $this->create($shift, $userId, CashMovementType::Receipt, $amount, $reason);
    }

    private function create(CashShift $shift, int $userId, CashMovementType $type, float $amount, string $reason): CashMovement
    {
        if ($amount <= 0) {
            throw new DomainException('O valor da movimentação deve ser maior que zero.');
        }

        if (empty(trim($reason))) {
            throw new DomainException('A justificativa da movimentação é obrigatória.');
        }

        return DB::transaction(function () use ($shift, $userId, $type, $amount, $reason): CashMovement {
            $lockedShift = CashShift::query()->lockForUpdate()->findOrFail($shift->id);

            if (! $lockedShift->isOpen()) {
                throw new DomainException('Não é possível movimentar um caixa fechado.');
            }

            return $lockedShift->movements()->create([
                'user_id' => $userId,
                'type' => $type,
                'amount' => number_format($amount, 2, '.', ''),
                'reason' => trim($reason),
            ]);
        });
    }
}
