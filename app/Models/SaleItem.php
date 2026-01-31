<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $guarded = [];

    // 🔹 FIX: This was missing and caused your error
    public function sale() 
    { 
        return $this->belongsTo(Sale::class); 
    }

    public function productItem() 
    { 
        return $this->belongsTo(ProductItem::class); 
    }

    protected static function booted()
    {
        static::created(function ($saleItem) {
            // Ensure ProductItem constants are defined in ProductItem model
            $saleItem->productItem->update(['status' => 'sold']);
        });
        
        static::deleted(function ($saleItem) {
            $saleItem->productItem->update(['status' => 'in_stock']);
        });
    }
}