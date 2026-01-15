<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockTransfer extends Model
{
    protected $guarded = [];

    // Relationships
    public function fromStore() { return $this->belongsTo(Store::class, 'from_store_id'); }
    public function toStore() { return $this->belongsTo(Store::class, 'to_store_id'); }
    public function items() { return $this->hasMany(StockTransferItem::class); }

    // --- LOGIC: SENDING (Lock the items) ---
    public function markAsSent()
    {
        DB::transaction(function () {
            $this->update(['status' => 'in_transit']);
            
            foreach ($this->items as $item) {
                // Find the ring and mark it as 'transfer'
                $item->productItem->update([
                    'status' => ProductItem::STATUS_TRANSFER,
                    'is_locked' => true // Prevent it from being sold
                ]);
            }
        });
    }

    // --- LOGIC: RECEIVING (Unlock and Move) ---
    public function markAsReceived()
    {
        DB::transaction(function () {
            $this->update(['status' => 'completed']);

            foreach ($this->items as $item) {
                // Find the ring and move it to the NEW Store
                $item->productItem->update([
                    'status' => ProductItem::STATUS_IN_STOCK,
                    'is_locked' => false,
                    'store_id' => $this->to_store_id // <--- The actual move
                ]);
            }
        });
    }
}