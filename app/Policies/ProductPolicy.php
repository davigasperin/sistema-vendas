<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageProducts();
    }

    public function update(User $user, Product $product): bool
    {
        if (!$user->canManageProducts()) {
            return false;
        }

        return !$product->saleItems()->exists();
    }

    public function delete(User $user, Product $product): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }

        return !$product->saleItems()->exists();
    }

    public function adjustStock(User $user, Product $product): bool
    {
        return $user->canManageProducts();
    }

    public function toggleActive(User $user, Product $product): bool
    {
        return $user->canManageProducts();
    }
}