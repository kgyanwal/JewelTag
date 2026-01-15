<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define Permissions
        $permissions = [
            'view.dashboard',
            
            // Sales
            'create.sale', 'edit.sale', 'delete.sale', 'view.sale', 'void.sale', 'process.refund',
            
            // Customers
            'view.customers', 'create.customer', 'edit.customer', 'delete.customer',
            
            // Inventory
            'view.inventory', 'create.product', 'edit.product', 'delete.product', 'manage.stock',
            
            // Suppliers
            'view.suppliers', 'create.supplier', 'edit.supplier', 'delete.supplier', 'create.supplier.order',
            
            // User Management
            'view.users', 'create.user', 'edit.user', 'delete.user', 'assign.roles', 'manage.permissions',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 3. Create Roles
        $roles = [
            'Superadmin' => [], // Gets all permissions
            'Administration' => ['view.dashboard', 'view.users', 'create.user', 'edit.user', 'assign.roles', 'view.suppliers', 'view.inventory'],
            'Manager' => ['view.dashboard', 'create.sale', 'view.sale', 'view.customers', 'view.inventory', 'view.suppliers'],
            'Sales' => ['view.dashboard', 'create.sale', 'view.sale', 'view.customers'],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            
            if ($roleName === 'Superadmin') {
                $role->givePermissionTo(Permission::all());
            } else {
                $role->givePermissionTo($rolePermissions);
            }
        }

        // 4. Assign Superadmin to the First User
        $user = User::first();
        if ($user) {
            $user->assignRole('Superadmin');
        }
    }
}