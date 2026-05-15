<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Expense extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_EXPENSE = 'expense';
    public const TYPE_INCOME = 'income';

    protected $fillable = [
        'description',
        'amount',
        'due_date',
        'paid_date',
        'category_id',
        'type',
        'status',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
        'type' => 'string',
        'status' => 'string',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OVERDUE)
            ->orWhere(function ($q) {
                $q->where('status', self::STATUS_PENDING)
                    ->where('due_date', '<', now()->toDateString());
            });
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }
}