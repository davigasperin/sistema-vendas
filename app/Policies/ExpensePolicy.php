<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Expense $expense): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageExpenses();
    }

    public function update(User $user, Expense $expense): bool
    {
        if (! $user->canManageExpenses()) {
            return false;
        }

        return $expense->status !== Expense::STATUS_PAID;
    }

    public function delete(User $user, Expense $expense): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        return $expense->status !== Expense::STATUS_PAID;
    }

    public function markPaid(User $user, Expense $expense): bool
    {
        return $user->canManageExpenses()
            && $expense->status === Expense::STATUS_PENDING;
    }

    public function report(User $user): bool
    {
        return $user->canManageExpenses();
    }
}
