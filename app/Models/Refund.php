<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $guarded = [];
protected $casts = [
    'refunded_items' => 'array', // 🔹 Crucial for the CheckboxList to work
];
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function processor(): BelongsTo { return $this->belongsTo(User::class, 'processed_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}