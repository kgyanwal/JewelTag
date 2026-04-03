<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sale_id',
        'custom_order_id',
        'amount',
        'method',
        'paid_at',
        'store_id',
    ];

    /**
     * Model Boot Logic
     * Automatically handles store assignment during creation.
     */
    protected static function booted()
    {
        static::creating(function ($payment) {
            // 1. If store_id isn't manually set, try to get it from the parent Sale
            if (!$payment->store_id && $payment->sale_id) {
                // Since this is a tenant model, it queries within the tenant database
                $payment->store_id = Sale::find($payment->sale_id)?->store_id;
            }

            // 2. If it's a Custom Order payment (no sale yet), get it from the custom order
            if (!$payment->store_id && $payment->custom_order_id) {
                $payment->store_id = CustomOrder::find($payment->custom_order_id)?->store_id;
            }

            // 3. Final Fallback: Use the logged-in staff member's store or default to 1
            if (!$payment->store_id) {
                $payment->store_id = auth()->user()?->store_id ?? 1;
            }
        });
    }
    /**
     * Relationship back to the Sale.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
    public function customOrder()
    {
        return $this->belongsTo(CustomOrder::class);
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
