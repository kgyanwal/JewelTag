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