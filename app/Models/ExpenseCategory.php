<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'type'];

    protected $casts = [
        'type' => 'string',
    ];

    protected $attributes = [
        'color' => '#6c757d',
        'type' => 'expense',
    ];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }
}
