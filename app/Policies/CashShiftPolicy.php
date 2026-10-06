<?php

namespace App\Policies;

use App\Models\CashShift;
use App\Models\User;

class CashShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageSales() || $user->canManageExpenses();
    }

    public function view(User $user, CashShift $shift): bool
    {
        return $user->isAdmin() || $user->role?->value === 'financial' || $shift->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canManageSales();
    }

    public function addMovement(User $user, CashShift $shift): bool
    {
        return $user->canManageSales() && $shift->user_id === $user->id;
    }

    public function close(User $user, CashShift $shift): bool
    {
        return $user->canManageSales() && $shift->user_id === $user->id;
    }
}
