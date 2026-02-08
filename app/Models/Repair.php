<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Repair extends Model
{
    // 🔹 Ensure this is empty to allow all fields, or explicitly add 'reported_issue' to $fillable
    protected $guarded = []; 

    protected static function booted()
    {
        static::creating(function ($repair) {
            $repair->repair_no = 'RPR-' . now()->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            $repair->store_id = auth()->user()->store_id ?? 1;
            $repair->staff_id = auth()->id();
            
            // 🔹 DEFAULT FALLBACK: In case the form field is empty
            if (empty($repair->reported_issue)) {
                $repair->reported_issue = 'General Maintenance / Service';
            }
        });
    }

    public function originalProduct(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class, 'original_product_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function saleItem()
{
    return $this->hasOne(SaleItem::class, 'repair_id');
}

public function sale()
{
    // Access the sale through the sale item
    return $this->hasOneThrough(
        \App\Models\Sale::class,
        \App\Models\SaleItem::class,
        'repair_id', // Foreign key on sale_items table
        'id',        // Foreign key on sales table
        'id',        // Local key on repairs table
        'sale_id'    // Local key on sale_items table
    );
}
}