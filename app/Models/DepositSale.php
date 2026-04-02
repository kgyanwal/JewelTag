<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepositSale extends Model
{
    use LogsActivity;

    protected $table = 'deposit_sales';

    protected $fillable = [
        'deposit_no',
        'customer_id',
        'sale_id',
        'total_amount',
        'amount_paid',
        'balance_due',
        'status',
        'sales_person',
        'staff_list',
        'start_date',
        'last_paid_date',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'total_amount'   => 'decimal:2',
        'amount_paid'    => 'decimal:2',
        'balance_due'    => 'decimal:2',
        'start_date'     => 'date',
        'last_paid_date' => 'date',
        'due_date'       => 'date',
        'staff_list'     => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class)->with('items.productItem', 'payments');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->balance_due <= 0;
    }

    public function getPaymentProgressAttribute(): int
    {
        if ($this->total_amount <= 0) return 0;
        return min(100, (int) round(($this->amount_paid / $this->total_amount) * 100));
    }

    public function getStaffDisplayAttribute(): string
    {
        $list = $this->staff_list ?? [];
        if (empty($list)) return $this->sales_person ?? '—';
        return implode(' / ', (array) $list);
    }
}