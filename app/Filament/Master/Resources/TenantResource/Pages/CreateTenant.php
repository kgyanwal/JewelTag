<?php

namespace App\Filament\Master\Resources\TenantResource\Pages;

use App\Filament\Master\Resources\TenantResource;
use App\Filament\Master\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\License;
use App\Models\MasterAuditLog;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    public string $domainUrl = '';

    // 🚀 NEW — hard stop: no tenant can be created without the MSA signed.
    // Backs up the form-level warning banner with a real server-side check —
    // someone can't bypass it by submitting via keyboard shortcut, API, etc.
    protected function beforeCreate(): void
    {
        if (empty($this->data['msa_agreed_at'])) {
            Notification::make()
                ->title('Agreement Required')
                ->body('You must view and sign the Subscription Agreement before creating this store.')
                ->danger()
                ->send();
            $this->halt();
        }
    }

    /**
     * Step 1: Extract domain and cleanup data before saving to 'tenants' table
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->domainUrl = $data['domain'];

        // Remove non-tenant-table fields so create() doesn't fail
        unset($data['domain']);

        // ── NEW: Plan defaults ────────────────────────────────────────
        $data['plan_id']       = $data['plan_id']       ?? Plan::where('slug', 'basic')->value('id');
        $data['plan_status']   = $data['plan_status']   ?? 'trial';
        $data['trial_ends_at'] = $data['trial_ends_at'] ?? now()->addDays(14);

        // 🚀 NEW — the License/Subscription/legal fields (license_plan,
        // license_expires_at, billing_cycle, msa_agreed_at, msa_agreed_ip)
        // don't belong on the tenants table itself. Strip them here so
        // create() doesn't choke on unknown columns — afterCreate() reads
        // them straight from $this->form->getRawState() instead, same
        // pattern already used for admin_name/admin_email/etc.
        unset(
            $data['license_plan'],
            $data['license_expires_at'],
            $data['billing_cycle'],
            $data['msa_agreed_at'],
            $data['msa_agreed_ip'],
            $data['agreement_required_warning'],
        );

        return $data;
    }

    /**
     * Step 2: Post-creation logic for Domains, Initial User, Subscription & License
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

        // 🚀 NEW — every new tenant now automatically gets a matching
        // Subscription and License record in the CENTRAL database, created
        // right alongside the tenant itself. Going forward, no tenant can
        // exist without both — beforeCreate() already guarantees the MSA
        // was signed before we even got here.
        $planSlug = Plan::find($data['plan_id'])?->slug ?? 'professional';

        $tierMap = [
            'basic'      => 'starter',
            'pro'        => 'professional',
            'enterprise' => 'enterprise',
        ];
        $planTier = $tierMap[$planSlug] ?? 'professional';

        Subscription::create([
            'tenant_id'            => $tenant->id,
            'plan_tier'            => $planTier,
            'billing_cycle'        => $data['billing_cycle'] ?? 'monthly',
            'status'               => match ($data['plan_status'] ?? 'trial') {
                'trial'     => 'trialing',
                'active'    => 'active',
                'suspended' => 'past_due',
                'cancelled' => 'canceled',
                default     => 'trialing',
            },
            'current_period_start' => now(),
            'current_period_end'   => $data['plan_expires_at'] ?? now()->addMonth(),
            'trial_ends_at'        => $data['trial_ends_at'] ?? null,
            'msa_version'          => 'v1.0-2026',
            'msa_agreed_at'        => $data['msa_agreed_at'],
            'msa_agreed_ip'        => $data['msa_agreed_ip'],
        ]);

        License::create([
            'tenant_id'   => $tenant->id,
            'license_key' => License::generate(),
            'plan'        => $data['license_plan'] ?? 'professional',
            'status'      => 'active',
            'expires_at'  => $data['license_expires_at'] ?? now()->addYear(),
        ]);

        MasterAuditLog::record(
            action: 'tenant_provisioned_with_legal',
            fieldLabel: 'Subscription & License',
            newValue: "Plan: {$planTier}, Signed: {$data['msa_agreed_at']}",
            tenantId: $tenant->id,
            tenantName: $tenant->id,
            severity: 'info',
        );

        Notification::make()
            ->title('Store Provisioned')
            ->body("Subscription and License records created automatically for {$tenant->id}.")
            ->success()
            ->send();
    }
}