<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MonthClose;
use App\Models\User;

class MonthClosePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MonthClose $monthClose): bool
    {
        return true;
    }

    public function close(User $user): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        return $user->isAdmin() || $user->role === UserRole::Financial;
    }
}
