<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    const STATUS_IN_STOCK = 'in_stock';
    const STATUS_SOLD = 'sold';
    // app/Models/ProductItem.php

protected $fillable = [
    'barcode',
    'supplier_id',
    'store_id',
    'supplier_code',
    'form_type',
    'department',
    'category',
    'metal_type',
    'size',          // 🔹 Add this
    'metal_weight',  // 🔹 Add this
    'cost_price',
    'retail_price',
    'web_price',
    'discount_percent',
    'custom_description', // 🔹 Add this
    'serial_number',
    'component_qty',
    'status',
];

    // 🔹 ADVANCED: Automatically determine status based on Qty
    public function getStatusAttribute($value)
    {
        return $this->qty > 0 ? self::STATUS_IN_STOCK : self::STATUS_SOLD;
    }

    public function productTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}