<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;
    use SoftDeletes;

    // This allows us to manually name our stores (e.g., 'diamondsquare')
    protected $fillable = [
        'id',
        'data',
        'is_active',
        'plan_id',
        'plan_status',
        'trial_ends_at',
        'plan_expires_at',
        'suspended_at',
        'suspension_reason',
        'last_login_at', 
    ];

    protected $casts = [
        'trial_ends_at'   => 'datetime',
        'plan_expires_at' => 'datetime',
        'suspended_at'    => 'datetime',
        'last_login_at'   => 'datetime',
        'is_active'       => 'boolean',
    ];

    /**
     * Tell stancl/tenancy these are real DB columns, not virtual
     * attributes to be stuffed into the `data` JSON blob.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'is_active',
            'plan_id',
            'plan_status',
            'trial_ends_at',
            'plan_expires_at',
            'suspended_at',
            'suspension_reason',
             'last_login_at',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(\App\Models\Plan::class);
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->plan?->hasFeature($feature) ?? false;
    }

    public function withinLimit(string $limitKey, int $current): bool
    {
        return $this->plan?->withinLimit($limitKey, $current) ?? false;
    }
}