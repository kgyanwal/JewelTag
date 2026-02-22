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
    // 🚨 FIX 1: Merge traits correctly. Remove the duplicate 'use HasRoles'
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
        if ($panel->getId() === 'master') {
            return true; 
        }

        // Inside a store, ensure the user has at least one role to enter
        return $this->roles()->exists();
    }

    /**
     * 🔹 PERMISSION SHIELD
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // 🚨 FIX 2: Corrected the tenancy check
        // If tenancy isn't initialized, we are in the Master database.
        if (!function_exists('tenancy') || !tenancy()->initialized) { 
             return true; 
        }

        // Inside a store, run the actual Spatie check
        return $this->traitHasPermissionTo($permission, $guardName);
    }
    // --- Relationships ---

   public function store()
{
    // Adjust 'store_id' if your foreign key column has a different name
    return $this->belongsTo(Store::class, 'store_id');
}

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // --- Attributes ---

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function getRoleNamesAttribute()
    {
        return $this->roles->pluck('name')->join(', ');
    }

    public function getPrimaryRoleAttribute()
    {
        return $this->roles->first()->name ?? 'No Role';
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }
}