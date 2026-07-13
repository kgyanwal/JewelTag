<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exchange extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'exchange_no',
        'customer_id',
        'store_id',
        'requested_by',
        'approved_by',
        'original_sale_id',
        'returned_items',
        'total_credit',
        'new_sale_id',
        'new_sale_amount',
        'difference_amount',
        'difference_payment_method',
        'exchange_type',
        'status',
        'reason',
        'staff_notes',
        'rejection_reason',
        'approved_at',
        'completed_at',
          'new_items',
    'new_items_subtotal',
    'new_items_tax',
    'new_custom_order_id',
    'new_repair_id',
    'is_split_payment',
    'split_payments',
    ];

    protected $casts = [
        'returned_items'  => 'array',
        'total_credit'    => 'decimal:2',
        'new_sale_amount' => 'decimal:2',
        'difference_amount' => 'decimal:2',
        'approved_at'     => 'datetime',
        'completed_at'    => 'datetime',
          'returned_items'   => 'array',
    'new_items'        => 'array',
    'split_payments'   => 'array',
    'is_split_payment' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
public function newCustomOrder()
{
    return $this->belongsTo(\App\Models\CustomOrder::class, 'new_custom_order_id');
}

public function newRepair()
{
    return $this->belongsTo(\App\Models\Repair::class, 'new_repair_id');
}
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function originalSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'original_sale_id');
    }

    public function newSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'new_sale_id');
    }

    public function getExchangeTypeLabel(): string
    {
        return match($this->exchange_type) {
            'upgrade'    => '⬆️ Upgrade (Customer pays difference)',
            'downgrade'  => '⬇️ Downgrade (Store refunds difference)',
            'same_value' => '↔️ Same Value Exchange',
            default      => ucfirst($this->exchange_type),
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending_approval' => 'warning',
            'approved'         => 'info',
            'completed'        => 'success',
            'rejected'         => 'danger',
            'cancelled'        => 'gray',
            default            => 'gray',
        };
    }

    // Generate unique exchange number
    public static function generateExchangeNo(): string
    {
        $prefix = 'EX-' . now()->format('ymd') . '-';
        $count  = static::whereDate('created_at', today())->count() + 1;
        while (static::where('exchange_no', $prefix . $count)->exists()) {
            $count++;
        }
        return $prefix . $count;
    }
    protected static function booted(): void
{
    static::creating(function (Exchange $exchange) {
        if (empty($exchange->exchange_no)) {
            $exchange->exchange_no = static::generateExchangeNo();
        }
        if (empty($exchange->requested_by)) {
            $exchange->requested_by = auth()->id();
        }
        if (empty($exchange->store_id)) {
            $exchange->store_id = auth()->user()->store_id
                ?? \App\Models\Store::first()?->id
                ?? 1;
        }
        if (empty($exchange->status)) {
            $exchange->status = 'pending_approval';
        }
        // Calculate difference
        $exchange->difference_amount = floatval($exchange->new_sale_amount) - floatval($exchange->total_credit);

        // Auto exchange type from difference
        $diff = floatval($exchange->difference_amount);
        if ($diff > 0)       $exchange->exchange_type = 'upgrade';
        elseif ($diff < 0)   $exchange->exchange_type = 'downgrade';
        // if exactly 0, keep whatever was set (same_value)
    });
}
}