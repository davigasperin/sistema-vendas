<?php

namespace App\Services;

use App\Models\MonthClose;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPeriodService
{
    private const LOCK_YEAR = 2000;

    private const LOCK_MONTH = 1;

    public function lockForUpdate(): void
    {
        DB::table('accounting_period_locks')->insertOrIgnore([
            'year' => self::LOCK_YEAR,
            'month' => self::LOCK_MONTH,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('accounting_period_locks')
            ->where('year', self::LOCK_YEAR)
            ->where('month', self::LOCK_MONTH)
            ->update(['updated_at' => now()]);
    }

    public function assertOpen(mixed ...$dates): void
    {
        foreach ($dates as $date) {
            if ($date === null || $date === '') {
                continue;
            }

            $parsed = Carbon::parse($date);

            if (MonthClose::where('year', $parsed->year)->where('month', $parsed->month)->exists()) {
                throw ValidationException::withMessages([
                    'month_close' => 'O mês de referência já está fechado.',
                ]);
            }
        }
    }
}
