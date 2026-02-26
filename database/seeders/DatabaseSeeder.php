<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Check if we are inside a Tenant Database
        if (function_exists('tenancy') && tenancy()->initialized) {
            
            // 🚀 ONLY seed the Roles and Permissions
            // This is required so the 'Superadmin' role exists 
            // before we assign it to the user created via UI.
            $this->call(RolePermissionSeeder::class);

            // ❌ REMOVE the User::create block from here
        }
    }
}