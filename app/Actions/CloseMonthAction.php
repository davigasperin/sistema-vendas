<?php

namespace App\Actions;

use App\Enums\CashShiftStatus;
use App\Models\CashShift;
use App\Models\MonthClose;
use App\Models\User;
use App\Queries\ReceivablesQuery;
use App\Services\AccountingPeriodService;
use DomainException;
use Illuminate\Support\Facades\DB;

class CloseMonthAction
{
    public function __construct(
        private ReceivablesQuery $receivablesQuery,
        private AccountingPeriodService $accountingPeriodService,
    ) {}

    public function __invoke(int $year, int $month, User $user): MonthClose
    {
        if ($year < 2000 || $year > 2100) {
            throw new DomainException('Ano inválido para fechamento mensal.');
        }

        if ($month < 1 || $month > 12) {
            throw new DomainException('Mês inválido para fechamento mensal.');
        }

        if ($year > now()->year || ($year === now()->year && $month > now()->month)) {
            throw new DomainException('Não é possível fechar um mês futuro.');
        }

        return DB::transaction(function () use ($year, $month, $user): MonthClose {
            $this->accountingPeriodService->lockForUpdate();

            if (CashShift::query()->where('status', CashShiftStatus::Open)->exists()) {
                throw new DomainException('Existem caixas abertos. Feche todos os caixas antes do fechamento mensal.');
            }

            if (MonthClose::query()->where('year', $year)->where('month', $month)->exists()) {
                throw new DomainException('Este mês já está fechado.');
            }

            return MonthClose::create([
                'year' => $year,
                'month' => $month,
                'totals' => $this->receivablesQuery->monthSnapshot($year, $month),
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);
        });
    }
}
