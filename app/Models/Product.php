<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'description', 'price', 'stock', 'active', 'low_stock_threshold'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'low_stock_threshold' => 'integer',
        'active' => 'boolean',
    ];

    protected $attributes = [
        'low_stock_threshold' => 5,
    ];

    /**
     * @return HasMany<SaleItem, $this>
     */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('active', true)->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', '%'.$search.'%');
    }
}
