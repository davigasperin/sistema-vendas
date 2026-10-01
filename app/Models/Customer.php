<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'address', 'birth_date'];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    public function scopeRecent(Builder $query, int $limit = 5): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeNewThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }
}