<?php

namespace App\Models;

// 🔹 CRITICAL IMPORTS for Filament and Roles
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

// 🔹 Must "implements FilamentUser" to allow panel access
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'store_id',
        'base_commission_rate',
        'phone',
        'is_active',
        'employee_code',
        'pin_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'base_commission_rate' => 'decimal:2',
    ];

    /**
     * 🔹 FILAMENT ACCESS CONTROL
     * This method is required by the FilamentUser interface.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Grants access only if user is active and has the 'Superadmin' role
        return $this->is_active && $this->hasRole('Superadmin');
    }

    // --- Relationships ---

    public function store()
    {
        return $this->belongsTo(Store::class);
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