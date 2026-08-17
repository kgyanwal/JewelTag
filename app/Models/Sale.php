<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'split_payments' => 'array',
        'sales_person_list' => 'array',
        'created_at' => 'datetime',
        'special_jobs'     => 'array',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }

    public function store(): BelongsTo
    {

        return $this->belongsTo(Store::class);
    }
    public function laybuy()
    {
        return $this->hasOne(Laybuy::class, 'sale_id');
    }

    /**
     * Model Boot Logic
     */
    protected static function booted()
    {
        static::creating(function ($sale) {
            $sale->store_id = $sale->store_id ?? 1;

            if (empty($sale->invoice_number)) {
                // 1. Get Prefix (e.g., 'D')
                $prefix = DB::table('site_settings')
                    ->where('key', 'barcode_prefix')
                    ->value('value') ?? 'D';

                // 2. Find the latest invoice that is NOT a long date string
                // We look for numbers where the length is small (e.g., Prefix + 4 digits = 5)
                $lastSale = self::withTrashed()
                    ->where('invoice_number', 'LIKE', "{$prefix}%")
                    ->whereRaw("LENGTH(invoice_number) < 9") // 🚀 IGNORES D31126005 (length 9)
                    ->orderByRaw('CAST(REPLACE(invoice_number, ?, "") AS UNSIGNED) DESC', [$prefix])
                    ->first();

                if ($lastSale) {
                    // Strip prefix and increment
                    $lastNumber = (int) str_replace($prefix, '', $lastSale->invoice_number);
                    $nextNumber = $lastNumber + 1;
                } else {
                    // 🚀 STARTING POINT
                    $nextNumber = 5001;
                }

                $sale->invoice_number = $prefix . $nextNumber;
            }
        });
      // WITH:
static::saving(function ($sale) {
    // Resolve store timezone — use store linked to this sale, or app default
    $tz   = optional(\App\Models\Store::find($sale->store_id))->timezone
            ?? config('app.timezone', 'UTC');
    $now  = \Illuminate\Support\Carbon::now($tz);

    // ── 1. New sale becoming completed for first time ──
    if ($sale->status === 'completed' && empty($sale->completed_at)) {
        $sale->completed_at = $now;
    }

    // ── 2. Imported sale: balance_due just dropped to 0 from a real payment ──
    if (
        $sale->status === 'completed'        &&
        floatval($sale->balance_due) <= 0.01 &&
        $sale->isDirty('balance_due')        &&
        floatval($sale->getOriginal('balance_due')) > 0.01
    ) {
        $sale->completed_at = $now;
    }
});
    }

    public function getCustomerNameAttribute(): string
    {
        return $this->customer
            ? "{$this->customer->first_name} {$this->customer->last_name}"
            : 'Walk-in';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'customer.first_name', 'customer.last_name'];
    }

    public function getGlobalSearchResultTitle(): string
    {
        return "Invoice: " . $this->invoice_number;
    }
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function salePayments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }
    public function getGlobalSearchResultDetails(): array
    {
        return [
            'Customer' => $this->customer?->name ?? 'Walk-in',
        ];
    }
    public function auditLogs()
    {
        return $this->hasMany(SaleAuditLog::class)->latest();
    }
}
