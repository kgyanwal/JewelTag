<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    // Faqs live on the central/master DB, shared across all tenant stores
    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function getCategoryOptions(): array
    {
        return [
            'general'   => 'General',
            'sales'     => 'Sales & POS',
            'inventory' => 'Inventory',
            'repairs'   => 'Repairs',
            'reports'   => 'Reports',
            'shopify'   => 'Shopify Sync',
            'billing'   => 'Billing',
            'account'   => 'Account & Settings',
        ];
    }
}