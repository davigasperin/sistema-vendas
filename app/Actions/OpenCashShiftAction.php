<?php

namespace App\Actions;

use App\Enums\CashShiftStatus;
use App\Models\CashShift;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class OpenCashShiftAction
{
    public function __invoke(int $userId, float $initialAmount = 0.0, ?string $notes = null): CashShift
    {
        if ($initialAmount < 0) {
            throw new DomainException('O fundo de troco inicial não pode ser negativo.');
        }

        return DB::transaction(function () use ($userId, $initialAmount, $notes): CashShift {
            User::query()->lockForUpdate()->findOrFail($userId);

            $hasOpenShift = CashShift::query()
                ->where('user_id', $userId)
                ->where('status', CashShiftStatus::Open)
                ->exists();

            if ($hasOpenShift) {
                throw new DomainException('Já existe um caixa aberto para este operador.');
            }

            return CashShift::create([
                'user_id' => $userId,
                'opened_at' => now(),
                'initial_amount' => number_format($initialAmount, 2, '.', ''),
                'status' => CashShiftStatus::Open,
                'notes' => $notes,
            ]);
        });
    }
}
