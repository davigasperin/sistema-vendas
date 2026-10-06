<?php

namespace App\Models;

use App\Enums\CashMovementType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $cash_shift_id
 * @property int $user_id
 * @property CashMovementType $type
 * @property string $amount
 * @property string $reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CashShift $cashShift
 * @property-read User $user
 */
class CashMovement extends Model
{
    protected $fillable = [
        'cash_shift_id',
        'user_id',
        'type',
        'amount',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<CashShift, $this> */
    public function cashShift(): BelongsTo
    {
        return $this->belongsTo(CashShift::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
