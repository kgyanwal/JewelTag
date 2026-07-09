<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidScanLog extends Model
{
    protected $fillable = [
        'rfid_session_id', 'epc_code', 'rfid_code', 'rssi',
        'antenna_port', 'read_count', 'product_item_id',
        'match_status', 'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(RfidSession::class, 'rfid_session_id');
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }
}