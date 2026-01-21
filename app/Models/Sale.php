<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $guarded = [];
    protected $casts = ['sales_person_list' => 'array'];
    

    // 🔹 FIX: This must match the name used in SaleResource relationship('customer', ...)
    public function customer(): BelongsTo 
    { 
        return $this->belongsTo(Customer::class); 
    }

    public function items(): HasMany 
    { 
        return $this->hasMany(SaleItem::class); 
    }

    protected static function booted()
    {
        static::creating(function ($sale) {
            $sale->store_id = $sale->store_id ?? 1; 

            if (empty($sale->invoice_number)) {
                $latest = self::latest('id')->first();
                $sale->invoice_number = 'INV-' . date('Y') . '-' . str_pad(($latest->id ?? 0) + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}