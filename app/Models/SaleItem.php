<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleItem extends Model
{
    use SoftDeletes;
     use LogsActivity;
    protected $guarded = [];

    // 🔹 FIX: This was missing and caused your error
    public function sale() 
    { 
        return $this->belongsTo(Sale::class); 
    }
public function repair(): BelongsTo
    {
        return $this->belongsTo(Repair::class);
    }
   public function productItem(): BelongsTo
{
    return $this->belongsTo(ProductItem::class, 'product_item_id');
}
public function customOrder(): BelongsTo
{
    return $this->belongsTo(CustomOrder::class, 'custom_order_id');
}
public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
protected static function booted()
    {
        // 🚀 CHANGED TO 'saving' SO IT WORKS ON EDITS TOO
        static::saving(function ($saleItem) {
            // Force null or empty strings to be strict 0 floats
            $saleItem->discount_amount = floatval($saleItem->discount_amount ?? 0);
            $saleItem->discount_percent = floatval($saleItem->discount_percent ?? 0);
            $saleItem->discount = floatval($saleItem->discount ?? 0); 
            
            // Ensure sale_price_override doesn't pass null if cleared
            $saleItem->sale_price_override = $saleItem->sale_price_override ? floatval($saleItem->sale_price_override) : null;
            
            $saleItem->is_tax_free = $saleItem->is_tax_free ?? false;
        });

        static::created(function ($saleItem) {
            if ($saleItem->product_item_id && $saleItem->productItem) {
                $saleItem->productItem->update(['status' => 'sold']);
            }
        });

        static::deleted(function ($saleItem) {
            if ($saleItem->product_item_id && $saleItem->productItem) {
                $saleItem->productItem->update(['status' => 'in_stock']);
            }
        });
    }

}