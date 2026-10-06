<?php

namespace App\Models;

use App\Enums\CashShiftStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property string $initial_amount
 * @property string|null $final_amount_reported
 * @property string|null $final_amount_expected
 * @property string|null $difference
 * @property CashShiftStatus $status
 * @property string|null $notes
 * @property int|null $sales_count
 * @property-read User $user
 * @property-read Collection<int, CashMovement> $movements
 * @property-read Collection<int, Sale> $sales
 */
class CashShift extends Model
{
    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'initial_amount',
        'final_amount_reported',
        'final_amount_expected',
        'difference',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'initial_amount' => 'decimal:2',
            'final_amount_reported' => 'decimal:2',
            'final_amount_expected' => 'decimal:2',
            'difference' => 'decimal:2',
            'status' => CashShiftStatus::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isOpen(): bool
    {
        return $this->status === CashShiftStatus::Open;
    }
}
