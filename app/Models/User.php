<?php

namespace App\Models;

use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, TwoFactorAuthenticatable {
        hasPermissionTo as protected traitHasPermissionTo;
    }

    protected $fillable = [
        'name', 'email', 'username', 'phone', 'password',
        'employee_code', 'pin_code', 'is_landlord', // Removed store_id and is_active from fillable if rolled back
    ];

    protected $hidden = ['password', 'remember_token'];

    /**
     * 🔹 FILAMENT ACCESS CONTROL
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 1. MASTER PANEL: Access via Landlord flag (Central DB)
        if ($panel->getId() === 'master') {
            return (bool) $this->is_landlord;
        }

        // 2. TENANT PANEL: Access if tenancy is active and user has a role (Tenant DB)
        if (function_exists('tenancy') && tenancy()->initialized) {
            // We rely on the fact that the user exists in this specific tenant DB
            return $this->roles()->exists();
        }

        return false;
    }

    /**
     * 🔹 PERMISSION SHIELD
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (!function_exists('tenancy') || !tenancy()->initialized) { 
             return true; 
        }
        return $this->traitHasPermissionTo($permission, $guardName);
    }

    /**
     * 🔹 MFA SESSION ISOLATION
     */
    public function breezySessions()
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            return $this->hasMany(self::class, 'id', 'id')->whereRaw('1 = 0');
        }

        return $this->hasMany(
            config('filament-breezy.session_model', \Jeffgreco13\FilamentBreezy\Models\BreezySession::class),
            'authenticatable_id'
        )->where('authenticatable_type', $this->getMorphClass());
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}