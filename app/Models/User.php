<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles {
        hasPermissionTo as protected traitHasPermissionTo;
    }

    protected $fillable = [
        'name', 'email', 'username', 'phone', 'password',
        'store_id', 'base_commission_rate', 'is_active',
        'employee_code', 'pin_code',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'base_commission_rate' => 'decimal:2',
    ];

    /**
     * 🔹 FILAMENT ACCESS CONTROL
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. Check if we are on the Master Panel
        if ($panel->getId() === 'master') {
            return in_array(request()->getHost(), config('tenancy.central_domains', []));
        }

        // 2. Inside a Store: Ensure tenancy is ready and user has a role
        if (function_exists('tenancy') && tenancy()->initialized) {
            return $this->roles()->exists();
        }

        return false;
    }

    /**
     * 🔹 PERMISSION SHIELD
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // On Master: Allow all
        if (!function_exists('tenancy') || !tenancy()->initialized) { 
             return true; 
        }

        // Inside Store: Check Spatie Roles
        return $this->traitHasPermissionTo($permission, $guardName);
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRoleNamesAttribute()
    {
        return $this->roles->pluck('name')->join(', ');
    }
}