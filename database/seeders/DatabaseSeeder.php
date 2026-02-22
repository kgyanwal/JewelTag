<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
   public function run(): void
{
    // 1. Only run this if a Tenant is initialized (Inside a store DB)
    if (function_exists('tenancy') && tenancy()->initialized) {
        
        $this->call(RolePermissionSeeder::class);

        \App\Models\User::create([
            'name' => 'Store Admin',
            'username' => 'admin',
            'email' => 'admin@store.com',
            'password' => bcrypt('password'),
            'pin_code' => '1234',
            'is_active' => true, // 👈 This column exists in Tenant DB
        ])->assignRole('Superadmin');
        
    } else {
        // 2. Logic for Master Database (Optional)
        // Usually, we create the Master User via the terminal command or Tinker
        // to avoid conflicts with Tenant-only columns.
    }
}
}