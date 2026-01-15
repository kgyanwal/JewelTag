<?php


namespace App\Policies;

use App\Models\User;
use App\Models\Sale;

class SalePolicy
{
    public function viewAny(User $user): bool {
        return $user->hasPermissionTo('view.sale');
    }

    public function create(User $user): bool {
        return $user->hasPermissionTo('create.sale');
    }

    public function update(User $user, Sale $sale): bool {
        return $user->hasPermissionTo('edit.sale');
    }

    public function delete(User $user, Sale $sale): bool {
        return $user->hasPermissionTo('delete.sale');
    }
}