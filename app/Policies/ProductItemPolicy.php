<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductItem;

class ProductItemPolicy
{
    /**
     * Can the user see the Inventory menu?
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view.inventory');
    }

    /**
     * Can the user create new stock?
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create.product');
    }

    /**
     * Can the user edit jewelry details?
     */
    public function update(User $user, ProductItem $productItem): bool
    {
        return $user->hasPermissionTo('edit.product');
    }

    /**
     * Can the user delete stock?
     */
    public function delete(User $user, ProductItem $productItem): bool
    {
        return $user->hasPermissionTo('delete.product');
    }
}