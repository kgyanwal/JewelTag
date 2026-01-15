<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $guarded = [];

    public function productItem() { return $this->belongsTo(ProductItem::class); }

    // MAGIC: When a SaleItem is created, mark the Ring as SOLD
    protected static function booted()
    {
        static::created(function ($saleItem) {
            $saleItem->productItem->update(['status' => ProductItem::STATUS_SOLD]);
        });
        
        // If we delete the sale, mark Ring as IN STOCK again
        static::deleted(function ($saleItem) {
            $saleItem->productItem->update(['status' => ProductItem::STATUS_IN_STOCK]);
        });
    }
}
