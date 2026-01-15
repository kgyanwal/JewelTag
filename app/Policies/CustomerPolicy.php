<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool {
        return $user->hasPermissionTo('view.customers');
    }

    public function create(User $user): bool {
        return $user->hasPermissionTo('create.customer');
    }

    public function update(User $user, Customer $customer): bool {
        return $user->hasPermissionTo('edit.customer');
    }
    
}