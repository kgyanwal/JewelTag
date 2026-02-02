<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductItem extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [];

    const STATUS_IN_STOCK = 'in_stock';
    const STATUS_SOLD = 'sold';
    // app/Models/ProductItem.php

protected $fillable = [
    'barcode',
    'qty',
    'supplier_id',
    'store_id',
    'supplier_code',
    'form_type',
    'department',
    'category',
    'rfid_code',
    'metal_type',
    'size',          // 🔹 Add this
    'metal_weight',  // 🔹 Add this
    'diamond_weight',
    'cost_price',
    'retail_price',
    'web_price',
    'discount_percent',
    'custom_description', // 🔹 Add this
    'serial_number',
    'component_qty',
    'status',
    'is_trade_in',         // 🔹 MUST BE HERE
    'original_trade_in_no', // 🔹 MUST BE HERE
    'is_memo',
    'memo_vendor_id',
    'memo_status',
];

    // 🔹 ADVANCED: Automatically determine status based on Qty
    // public function getStatusAttribute($value)
    // {
    //     return $this->qty > 0 ? self::STATUS_IN_STOCK : self::STATUS_SOLD;
    // }

    public function productTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class);
    }
public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function memoVendor() {
    return $this->belongsTo(Supplier::class, 'memo_vendor_id');
}
//     protected static function booted()
// {
//     static::saving(function ($item) {
//         if ($item->qty <= 0) {
//             $item->status = 'sold';
//             $item->qty = 0;
//         }
//     });
// }

}