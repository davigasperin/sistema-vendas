<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleInstallment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    protected $fillable = [
        'sale_id',
        'installment_number',
        'amount',
        'due_date',
        'paid_date',
        'is_paid',
        'notes',
        'payment_method_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'is_paid' => 'boolean',
    ];

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_paid', false);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_paid', true);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_paid', false)
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    public function scopeBySale(Builder $query, int $saleId): Builder
    {
        return $query->where('sale_id', $saleId);
    }

    public function getStatusAttribute(): string
    {
        if ($this->is_paid) {
            return self::STATUS_PAID;
        }

        if ($this->due_date < now()->toDateString()) {
            return self::STATUS_OVERDUE;
        }

        return self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->is_paid;
    }

    public function isOverdue(): bool
    {
        return ! $this->is_paid && $this->due_date < now()->toDateString();
    }

    public function markAsPaid(?string $paidDate = null, ?int $paymentMethodId = null): self
    {
        $this->update([
            'is_paid' => true,
            'paid_date' => $paidDate ?? now()->toDateString(),
            'payment_method_id' => $paymentMethodId,
        ]);

        return $this;
    }

    public function markAsPending(): self
    {
        $this->update([
            'is_paid' => false,
            'paid_date' => null,
            'payment_method_id' => null,
        ]);

        return $this;
    }

    public static function checkOverdue(): void
    {
        self::where('is_paid', false)
            ->where('due_date', '<', now()->toDateString())
            ->update(['is_paid' => false]);
    }
}
