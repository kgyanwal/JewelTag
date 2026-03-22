<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    protected $fillable = [
        'sale_id', 'customer_id', 'store_id', 'amount', 
        'payment_method', 'original_payment_type', 
        'payment_date', 'is_deposit', 'is_layby', 'sales_person', 
        'notes', 'imported_at'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'imported_at' => 'datetime',
        'is_deposit' => 'boolean',
        'is_layby' => 'boolean',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}