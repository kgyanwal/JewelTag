<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'plan_tier',
        'billing_cycle',
        'status',
        'current_period_start',
        'current_period_end',
        'trial_ends_at',
        'canceled_at',
        'msa_version',
        'msa_agreed_at',
        'msa_agreed_ip',
        'contract_pdf_path',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'msa_agreed_at' => 'datetime',
    ];

    /**
     * Get the store/tenant that owns this subscription.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Helper to check if the subscription is currently valid
     */
    public function isValid(): bool
    {
        return in_array($this->status, ['active', 'trialing', 'past_due']);
    }
}