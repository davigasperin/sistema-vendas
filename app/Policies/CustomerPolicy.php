<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageCustomers();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->canManageCustomers();
    }

    public function delete(User $user, Customer $customer): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return ! $customer->sales()->exists();
    }
}
