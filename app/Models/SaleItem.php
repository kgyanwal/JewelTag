<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
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

   protected static function booted()
{
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