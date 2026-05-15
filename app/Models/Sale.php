<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id', 'payment_method_id',
        'total_amount', 'installments', 'discount', 'notes'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'installments' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleInstallments()
    {
        return $this->hasMany(SaleInstallment::class);
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByPaymentMethod(Builder $query, int $paymentMethodId): Builder
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    public function isPaidInInstallments(): bool
    {
        return $this->installments > 1;
    }
}