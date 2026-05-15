<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Sale $sale): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageSales();
    }

    public function update(User $user, Sale $sale): bool
    {
        if (!$user->canManageSales()) {
            return false;
        }

        $hasPaidInstallments = $sale->saleInstallments()
            ->where('is_paid', true)
            ->exists();

        return !$hasPaidInstallments;
    }

    public function delete(User $user, Sale $sale): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        $hasPaidInstallments = $sale->saleInstallments()
            ->where('is_paid', true)
            ->exists();

        return !$hasPaidInstallments;
    }

    public function restore(User $user, Sale $sale): bool
    {
        return $user->isAdmin();
    }

    public function downloadPdf(User $user, Sale $sale): bool
    {
        return true;
    }
}