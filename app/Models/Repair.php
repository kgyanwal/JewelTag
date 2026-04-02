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
        'items' => 'array',
        'sales_person_list' => 'array',
        'notified_at' => 'datetime',
        'repair_history' => 'array', // 🚀 CRITICAL: This allows us to store the message history
    ];

  protected static function booted()
{
    static::creating(function ($repair) {
        $repair->repair_no = 'RPR-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $repair->store_id  = auth()->user()->store_id ?? 1;
        $repair->staff_id  = auth()->id();

        // Pull first item's description as the legacy flat field
        if (!empty($repair->items) && is_array($repair->items)) {
            $repair->item_description = $repair->items[0]['item_description'] ?? 'General Maintenance / Service';
            $repair->reported_issue   = $repair->items[0]['reported_issue']   ?? 'General Maintenance / Service';
            $repair->estimated_cost   = collect($repair->items)->sum(fn($i) => (float)($i['estimated_cost'] ?? 0));
            $repair->final_cost       = collect($repair->items)->sum(fn($i) => (float)($i['final_cost'] ?? 0)) ?: null;
            $repair->is_warranty      = collect($repair->items)->every(fn($i) => $i['is_warranty'] ?? false);
            $repair->is_from_store_stock = collect($repair->items)->contains(fn($i) => $i['is_from_store_stock'] ?? false);
        } else {
            $repair->item_description = $repair->item_description ?? 'General Maintenance / Service';
            $repair->reported_issue   = $repair->reported_issue   ?? 'General Maintenance / Service';
        }
    });

    static::updating(function ($repair) {
        if (!empty($repair->items) && is_array($repair->items)) {
            $repair->item_description = $repair->items[0]['item_description'] ?? $repair->item_description ?? 'General Maintenance / Service';
            $repair->reported_issue   = $repair->items[0]['reported_issue']   ?? $repair->reported_issue   ?? 'General Maintenance / Service';
            $repair->estimated_cost   = collect($repair->items)->sum(fn($i) => (float)($i['estimated_cost'] ?? 0));
            $repair->final_cost       = collect($repair->items)->sum(fn($i) => (float)($i['final_cost'] ?? 0)) ?: null;
            $repair->is_warranty      = collect($repair->items)->every(fn($i) => $i['is_warranty'] ?? false);
            $repair->is_from_store_stock = collect($repair->items)->contains(fn($i) => $i['is_from_store_stock'] ?? false);
        }
    });
}
public function salesPerson(): BelongsTo
    {
        // This links the 'staff_id' (or sales_person_id) to the User model
        return $this->belongsTo(User::class, 'staff_id'); 
    }

    // 🚀 ADD THIS RELATIONSHIP (Needed for the header logo/info)
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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