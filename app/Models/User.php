<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    use HasFactory, HasRoles, Notifiable;

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

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // Attributes
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
     * * Note: Fixed recursion in your original version
     */
    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // This grants access if the user is active and has the Superadmin role
        return $this->is_active && $this->hasRole('Superadmin');
    }
}