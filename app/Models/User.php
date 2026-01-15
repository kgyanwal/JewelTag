<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, Notifiable;

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles);
        }
        return $this->hasRole($roles);
    }
}