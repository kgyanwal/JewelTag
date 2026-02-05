<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomOrder extends Model
{
    protected $guarded = [];

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