<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrder extends Model
{
    use LogsActivity;
    
    protected $guarded = [];

    // 🔹 VITAL: Cast dates and booleans for Filament
    protected $casts = [
        'due_date' => 'date',
        'expected_delivery_date' => 'date',
        'follow_up_date' => 'date',
        'is_customer_notified' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'staff_id'); }

    protected static function booted()
    {
        static::creating(function ($order) {
            $order->order_no = 'CUST-' . strtoupper(bin2hex(random_bytes(3)));
            $order->store_id = auth()->user()->store_id ?? 1;
            $order->staff_id = auth()->id();
        });
    }
}