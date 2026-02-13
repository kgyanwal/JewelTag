<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
class Supplier extends Model
{
     use LogsActivity;
    protected $guarded = [];
    use SoftDeletes;
    protected $casts = [
        'payment_terms_days' => 'integer',
        'order_limit' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'average_lead_time' => 'decimal:2',
        'quality_rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_preferred' => 'boolean',
        'lead_time_days' => 'integer',
        'total_orders' => 'integer',
    ];
    
    // Constants
    const TYPE_MANUFACTURER = 'manufacturer';
    const TYPE_WHOLESALER = 'wholesaler';
    const TYPE_DISTRIBUTOR = 'distributor';
    const TYPE_ARTISAN = 'artisan';
    const TYPE_OTHER = 'other';
    
    // Relationships
    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }
    // Add these methods to your Supplier model
public function getTypeLabelAttribute(): string
{
    return match($this->type) {
        'manufacturer' => 'Manufacturer',
        'wholesaler' => 'Wholesaler',
        'distributor' => 'Distributor',
        'artisan' => 'Artisan',
        'other' => 'Other',
        default => ucfirst($this->type),
    };
}

public function getFullAddressAttribute(): string
{
    $parts = [];
    if ($this->physical_street) $parts[] = $this->physical_street;
    if ($this->physical_suburb) $parts[] = $this->physical_suburb;
    if ($this->physical_city) $parts[] = $this->physical_city;
    if ($this->physical_state) $parts[] = $this->physical_state;
    if ($this->physical_postcode) $parts[] = $this->physical_postcode;
    if ($this->physical_country) $parts[] = $this->physical_country;
    
    return implode(', ', array_filter($parts));
}
    
    // Attributes
  

    
}