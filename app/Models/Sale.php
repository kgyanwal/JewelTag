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

    /**
     * Relationship to the Customer.
     */
    public function customer(): BelongsTo 
    { 
        return $this->belongsTo(Customer::class); 
    }

    /**
     * Relationship to the items within the sale.
     */
    public function items(): HasMany 
    { 
        return $this->hasMany(SaleItem::class); 
    }

    /**
     * Legacy reference to productItem if needed.
     */
    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }

    /**
     * Relationship to Laybuy if applicable.
     */
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
            // Ensure a store ID is assigned
            $sale->store_id = $sale->store_id ?? 1; 

            if (empty($sale->invoice_number)) {
                // 1. Fetch the dynamic prefix from site_settings (e.g., 'D')
                $prefix = DB::table('site_settings')
                    ->where('key', 'barcode_prefix')
                    ->value('value') ?? 'D';

                // 2. Find the highest existing invoice number that starts with this prefix
                // This prevents "Duplicate Entry" errors when records are deleted
                $latestInvoice = self::where('invoice_number', 'LIKE', "{$prefix}%")
                    ->orderBy('invoice_number', 'desc')
                    ->first();

                if ($latestInvoice) {
                    // Extract numeric part (e.g., "D001" -> 1)
                    $currentNumber = (int) preg_replace('/[^0-9]/', '', $latestInvoice->invoice_number);
                    $nextNumber = $currentNumber + 1;
                } else {
                    $nextNumber = 1;
                }

                // 3. Set the invoice number (e.g., D001, D002)
                $sale->invoice_number = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Accessor for Customer Name
     */
    public function getCustomerNameAttribute(): string
    {
        return $this->customer
            ? "{$this->customer->first_name} {$this->customer->last_name}"
            : 'Walk-in';
    }

    /**
     * Global Search Configuration
     */
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