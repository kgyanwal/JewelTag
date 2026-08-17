<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
    'name', 'slug', 'description', 'price_monthly', 'price_yearly',
    'is_active', 'is_popular', 'sort_order', 'badge_color',
    'max_users', 'max_items', 'max_locations',
    'max_custom_orders_month', 'max_repairs_month', 'max_laybuys', // ← ADD
    'feature_diamond_vault', 'feature_layaway', 'feature_api',
    'feature_sms', 'feature_crm', 'feature_advanced_analytics',
    'feature_exchange', 'feature_rfid', 'feature_multi_store',
    'feature_white_label', 'feature_custom_integrations',
    'custom_features',
];

    protected $casts = [
        'is_active'                    => 'boolean',
        'is_popular'                   => 'boolean',
        'feature_diamond_vault'        => 'boolean',
        'feature_layaway'              => 'boolean',
        'feature_api'                  => 'boolean',
        'feature_sms'                  => 'boolean',
        'feature_crm'                  => 'boolean',
        'feature_advanced_analytics'   => 'boolean',
        'feature_exchange'             => 'boolean',
        'feature_rfid'                 => 'boolean',
        'feature_multi_store'          => 'boolean',
        'feature_white_label'          => 'boolean',
        'feature_custom_integrations'  => 'boolean',
        'custom_features'              => 'array',
        'price_monthly'                => 'decimal:2',
        'price_yearly'                 => 'decimal:2',
        'max_users'                    => 'integer',
        'max_items'                    => 'integer',
        'max_locations'                => 'integer',
        'max_custom_orders_month'      => 'integer',
        'max_repairs_month'            => 'integer',
        'sort_order'                   => 'integer',
    ];

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    // ── Helper: check if a feature is enabled ────────────────────────
    public function hasFeature(string $feature): bool
    {
        $col = 'feature_' . $feature;
        return (bool) ($this->$col ?? false);
    }

    // ── Helper: check a limit (-1 = unlimited) ────────────────────────
    public function withinLimit(string $limitKey, int $current): bool
    {
        $max = (int) ($this->$limitKey ?? 0);
        return $max === -1 || $current < $max;
    }

    public static function getBasic(): ?self  { return self::where('slug', 'basic')->first(); }
    public static function getPro(): ?self    { return self::where('slug', 'pro')->first(); }
}