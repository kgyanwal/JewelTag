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
    ];

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
}