<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use LogsActivity;
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
public function productItem()
{
    return $this->belongsTo(ProductItem::class);
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
    // Sale.php
public function getCustomerNameAttribute(): string
{
    return $this->customer
        ? "{$this->customer->first_name} {$this->customer->last_name}"
        : 'Walk-in';
}
public function laybuy()
{
    return $this->hasOne(Laybuy::class, 'sale_id');
}

public static function getGloballySearchableAttributes(): array
{
    // Allows searching by invoice or customer name from the top bar
    return ['invoice_number', 'customer.name'];
}

public function getGlobalSearchResultTitle(): string
{
    // Displays the title in the search results dropdown
    return "Invoice: " . $this->invoice_number;
}

public function getGlobalSearchResultDetails(): array
{
    // Adds extra info (Customer name) to the search result
    return [
        'Customer' => $this->customer?->name ?? 'Walk-in',
    ];
}
}