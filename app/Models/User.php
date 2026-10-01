<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole|null $role
 * @property Carbon|null $email_verified_at
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin || $this->email === 'admin@sistema.com';
    }

    public function isActive(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function canManageSales(): bool
    {
        return $this->isActive() && in_array($this->role, [UserRole::Admin, UserRole::Seller], true);
    }

    public function canManageProducts(): bool
    {
        return $this->isActive() && in_array($this->role, [UserRole::Admin, UserRole::Seller], true);
    }

    public function canManageCustomers(): bool
    {
        return $this->isActive() && in_array($this->role, [UserRole::Admin, UserRole::Seller], true);
    }

    public function canManageExpenses(): bool
    {
        return $this->isActive() && in_array($this->role, [UserRole::Admin, UserRole::Financial], true);
    }
}
