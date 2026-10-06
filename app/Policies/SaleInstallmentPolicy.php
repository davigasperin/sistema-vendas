<?php

namespace App\Policies;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\SaleInstallment;
use App\Models\User;

class SaleInstallmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SaleInstallment $saleInstallment): bool
    {
        return true;
    }

    public function pay(User $user, SaleInstallment $saleInstallment): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        $sale = $saleInstallment->sale;

        if (! $sale || $sale->trashed() || $sale->status !== SaleStatus::Completed) {
            return false;
        }

        if ($user->isAdmin() || $user->role === UserRole::Financial) {
            return true;
        }

        return $user->role === UserRole::Seller
            && $sale->user_id === $user->id;
    }
}
