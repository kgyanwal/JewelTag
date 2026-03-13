<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Repair extends Model
{
    use LogsActivity;

    protected $guarded = []; 

    protected $casts = [
        'notified_at' => 'datetime',
        'repair_history' => 'array', // 🚀 CRITICAL: This allows us to store the message history
    ];

    protected static function booted()
    {
        static::creating(function ($repair) {
            $repair->repair_no = 'RPR-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $repair->store_id = auth()->user()->store_id ?? 1;
            $repair->staff_id = auth()->id();
            
            if (empty($repair->reported_issue)) {
                $repair->reported_issue = 'General Maintenance / Service';
            }
        });
    }

    public function originalProduct(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class, 'original_product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function saleItem(): HasOne
    {
        return $this->hasOne(SaleItem::class, 'repair_id');
    }

    public function sale()
    {
        return $this->hasOneThrough(
            \App\Models\Sale::class,
            \App\Models\SaleItem::class,
            'repair_id',
            'id',
            'id',
            'sale_id'
        );
    }
}