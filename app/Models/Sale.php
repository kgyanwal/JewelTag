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
        'sales_person_list' => 'array'
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
                // 1. Get Prefix from settings (e.g., 'D')
                $prefix = DB::table('site_settings')
                    ->where('key', 'barcode_prefix')
                    ->value('value') ?? 'D';

                // 2. Generate Date String: MMDDYY (e.g., 021326)
                $datePart = now()->format('mdy');

                // 3. Find the highest sequence number for TODAY ONLY
                $todayPattern = $prefix . $datePart . '%';
                
                $latestToday = self::where('invoice_number', 'LIKE', $todayPattern)
                    ->orderBy('invoice_number', 'desc')
                    ->first();

                if ($latestToday) {
                    // Extract all digits following the date part and increment
                    // This handles 01, 99, or 100+ correctly
                    $lastInvoice = $latestToday->invoice_number;
                    $sequencePart = substr($lastInvoice, strlen($prefix . $datePart));
                    $nextSequence = (int) $sequencePart + 1;
                } else {
                    // First sale of the day
                    $nextSequence = 1;
                }

                // 4. Combine: D + 021326 + 001 (padded to 3 digits)
                // Result: D021326001, D021326100, etc.
                $sale->invoice_number = $prefix . $datePart . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
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

    public function getGlobalSearchResultDetails(): array
    {
        return [
            'Customer' => $this->customer?->name ?? 'Walk-in',
        ];
    }
}