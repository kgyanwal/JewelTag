<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'points', 'balance_after', 'tier_at_time', 'multiplier',
        'qualifying_amount', 'sale_id', 'payment_id', 'refund_id', 'custom_order_id',
        'reason', 'user_id', 'idempotency_key',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function sale()     { return $this->belongsTo(Sale::class); }
}