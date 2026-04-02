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

    public function sale(): BelongsTo
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
        static::saving(function ($saleItem) {
            // ✅ Imported and non-stock items bypass all stock logic entirely
            if ($saleItem->import_source || $saleItem->is_non_stock) return;

            $saleItem->discount_amount   = floatval($saleItem->discount_amount ?? 0);
            $saleItem->discount_percent  = floatval($saleItem->discount_percent ?? 0);
            $saleItem->discount          = floatval($saleItem->discount ?? 0);
            $saleItem->sale_price_override = $saleItem->sale_price_override
                ? floatval($saleItem->sale_price_override)
                : null;
            $saleItem->is_tax_free = $saleItem->is_tax_free ?? false;
        });

        static::created(function ($saleItem) {
            // ✅ Triple guard — never touch inventory for imported/non-stock/unlinked items
            if ($saleItem->import_source || $saleItem->is_non_stock || !$saleItem->product_item_id) return;
            $saleItem->productItem?->update(['status' => 'sold']);
        });

        static::deleted(function ($saleItem) {
            // ✅ Same guard on delete — don't restore stock for imported items
            if ($saleItem->import_source || $saleItem->is_non_stock || !$saleItem->product_item_id) return;
            $saleItem->productItem?->update(['status' => 'in_stock']);
        });
    }
}