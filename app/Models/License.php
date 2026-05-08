<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class License extends Model
{
    // 🔒 Force this model to ALWAYS use the central database connection
    protected $connection = 'mysql'; 

    protected $fillable = [
        'license_key', 'tenant_id', 'plan', 'status',
        'max_users', 'expires_at', 'licensed_to', 'licensed_email',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        
        return true;
    }

    public static function generate(): string
    {
        return strtoupper(implode('-', [Str::random(4), Str::random(4), Str::random(4), Str::random(4)]));
    }
}