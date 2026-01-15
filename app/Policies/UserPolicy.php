<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Only users with 'view.users' permission can see the list
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view.users');
    }

    // Only users with 'create.user' can see the "New User" button
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create.user');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('edit.user');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('delete.user');
    }
}