<?php

namespace App\Filament\Master\Resources\TenantResource\Pages;

use App\Filament\Master\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    public string $domainUrl = '';

    /**
     * Step 1: Extract domain and cleanup data before saving to 'tenants' table
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->domainUrl = $data['domain'];
        
        // Remove non-tenant-table fields so create() doesn't fail
        unset($data['domain']);
        
        return $data;
    }

    /**
     * Step 2: Post-creation logic for Domains and Initial User
     */
    protected function afterCreate(): void
    {
        $tenant = $this->record;
        $data = $this->form->getRawState();

        // 1. Attach the domain in the Central Database
        $tenant->domains()->create([
            'domain' => $this->domainUrl
        ]);

        // 2. Run logic INSIDE the new Tenant Database
        $tenant->run(function () use ($data) {
            
            // Seed Permissions/Roles first
            Artisan::call('db:seed', [
                '--class' => 'RolePermissionSeeder',
                '--force' => true,
            ]);

            // Create the first user based on the form input
            $user = \App\Models\User::create([
                'name'      => $data['admin_name'],
                'email'     => $data['admin_email'],
                'username'  => strstr($data['admin_email'], '@', true), // Uses email prefix as username
                'password'  => Hash::make($data['admin_password']),
                'pin_code'  => $data['admin_pin'],
                'is_active' => true,
            ]);

            // Assign the Superadmin role defined in RolePermissionSeeder
            $user->assignRole('Superadmin');
        });
    }
}