<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockTransfer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'item_snapshot' => 'array',
        'actioned_at'   => 'datetime',
        'transfer_date' => 'datetime',
    ];

    public function fromStore() { return $this->belongsTo(Store::class, 'from_store_id'); }
    public function toStore()   { return $this->belongsTo(Store::class, 'to_store_id'); }
    public function items()     { return $this->hasMany(StockTransferItem::class); }

    public function markAsSent()
    {
        DB::transaction(function () {
            $this->update(['status' => 'in_transit']);
            foreach ($this->items as $item) {
                $item->productItem?->update(['status' => 'on_hold']);
            }
        });
    }

    public function markAsReceived()
    {
        DB::transaction(function () {
            $this->update([
                'status'      => 'accepted',
                'actioned_at' => now(),
            ]);
            foreach ($this->items as $item) {
                $item->productItem?->update([
                    'status'   => 'in_stock',
                    'store_id' => $this->to_store_id,
                ]);
            }
        });
    }
}