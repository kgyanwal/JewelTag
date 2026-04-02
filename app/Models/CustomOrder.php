<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrder extends Model
{
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'due_date'               => 'date',
        'expected_delivery_date' => 'date',
        'follow_up_date'         => 'date',
        'is_customer_notified'   => 'boolean',
        'notified_at'            => 'datetime',
        'items'                  => 'array', // ← add this
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function store(): BelongsTo    { return $this->belongsTo(Store::class); }
    public function staff(): BelongsTo    { return $this->belongsTo(User::class, 'staff_id'); }
    public function payments()            { return $this->hasMany(Payment::class); }

    protected static function booted()
    {
        static::creating(function ($order) {
            $order->order_no = 'CUST-' . strtoupper(bin2hex(random_bytes(3)));
            $order->store_id = auth()->user()->store_id ?? 1;
            $order->staff_id = auth()->id();
        });

        // Sync flat columns from first item for backward compatibility
        static::saving(function ($order) {
            if (!empty($order->items) && is_array($order->items)) {
                $first = $order->items[0];
                $order->product_name   = $first['product_name']   ?? $order->product_name;
                $order->metal_type     = $first['metal_type']     ?? $order->metal_type;
                $order->metal_weight   = $first['metal_weight']   ?? $order->metal_weight;
                $order->diamond_weight = $first['diamond_weight'] ?? $order->diamond_weight;
                $order->size           = $first['size']           ?? $order->size;
                $order->design_notes   = $first['design_notes']   ?? $order->design_notes;
                $order->quoted_price   = collect($order->items)->sum(fn($i) => (float)($i['quoted_price'] ?? 0));
            }
        });
    }
}