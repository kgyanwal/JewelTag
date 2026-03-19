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
    static::saving(function ($sale) {
            // 🚀 Automatically set completed_at when status changes to completed
            if ($sale->status === 'completed' && empty($sale->completed_at)) {
                $sale->completed_at = now();
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
    public function getGlobalSearchResultDetails(): array
    {
        return [
            'Customer' => $this->customer?->name ?? 'Walk-in',
        ];
    }
}