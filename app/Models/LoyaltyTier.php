<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $fillable = ['name', 'slug', 'min_spend', 'earn_multiplier', 'discount_percent', 'perks', 'sort_order', 'is_active'];

    protected $casts = [
        'min_spend'        => 'decimal:2',
        'earn_multiplier'  => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'perks'            => 'array',
        'is_active'        => 'boolean',
    ];

    public static function ordered()
    {
        return static::where('is_active', true)->orderBy('min_spend')->get();
    }
}