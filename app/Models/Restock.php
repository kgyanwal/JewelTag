<?php

// app/Models/Restock.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Restock extends Model
{
    protected $fillable = [
    'refund_id',
    'product_item_id',
    'stock_no',
    'salesperson_name', // 🔹 Add this
    'restock_fee',
    'quality_check',
    'notes',
    'status',
    'finalized_by',
    'finalized_at',
];

    /**
     * Relationship to the physical inventory item
     */
    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }

    /**
     * Relationship to the refund record
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * Relationship to the user who performed the final restock
     */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}