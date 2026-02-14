<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditItem extends Model
{
    protected $fillable = ['audit_id', 'product_item_id', 'rfid_code', 'scanned_at'];

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }
}