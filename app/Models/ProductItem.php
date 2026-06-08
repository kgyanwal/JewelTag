<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductItem extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected $guarded = [];

    const STATUS_IN_STOCK = 'in_stock';
    const STATUS_SOLD = 'sold';
    // app/Models/ProductItem.php

    protected $fillable = [
        'barcode',
        'qty',
        'supplier_id',
        'store_id',
        'supplier_code',
        'form_type',
        'department',
        'sub_department',
        'category',
        'rfid_code',
        'metal_type',
        'size',          // 🔹 Add this
        'metal_weight',  // 🔹 Add this
        'diamond_weight',
        'cost_price',
        'retail_price',
        'web_price',
        'discount_percent',
        'custom_description', // 🔹 Add this
        'serial_number',
        'component_qty',
        'status',
        'is_trade_in',         // 🔹 MUST BE HERE
        'original_trade_in_no', // 🔹 MUST BE HERE
        'is_memo',
        'memo_vendor_id',
        'memo_status',
        //new
        'shape',
        'color', //fix this colour in migration
        'clarity',
        'cut',
        'polish',
        'symmetry',
        'fluorescence',
        'measurements',
        'certificate_number',
        'certificate_agency',
        'markup',
        'web_item',
        'date_in',
        'inactivated_at',
        'inactivated_by',
        'inactivated_reason',
        'primary_image',
        'gallery_images',

    ];

    protected $casts = [
        'date_in' => 'datetime',
        'inactivated_at' => 'datetime',
        'is_trade_in' => 'boolean',
        'is_memo' => 'boolean',
        'is_lab_grown' => 'boolean',
        'web_item' => 'boolean',
        'markup' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'web_price' => 'decimal:2',
        'gallery_images' => 'array',
    ];

    // 🔹 ADVANCED: Automatically determine status based on Qty
    // public function getStatusAttribute($value)
    // {
    //     return $this->qty > 0 ? self::STATUS_IN_STOCK : self::STATUS_SOLD;
    // }

    public function productTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class);
    }
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
    public function memoVendor()
    {
        return $this->belongsTo(Supplier::class, 'memo_vendor_id');
    }
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_item_id');
    }

     public function getPrimaryImageUrlAttribute(): string
{
    try {
        if ($this->primary_image) {
            return \Illuminate\Support\Facades\Storage::url($this->primary_image);
        }

        $gallery = $this->gallery_images ?? [];
        if (!empty($gallery[0])) {
            return \Illuminate\Support\Facades\Storage::url($gallery[0]);
        }
    } catch (\Exception $e) {
        // fall through to placeholder
    }

    $category = strtolower($this->category ?? '');
    $placeholders = [
        'ring'     => '/placeholders/ring.svg',
        'necklace' => '/placeholders/necklace.svg',
        'bracelet' => '/placeholders/bracelet.svg',
        'earring'  => '/placeholders/earrings.svg',
        'pendant'  => '/placeholders/pendant.svg',
        'diamond'  => '/placeholders/diamond.svg',
    ];

    foreach ($placeholders as $key => $path) {
        if (str_contains($category, $key)) {
            return $path;
        }
    }

    return '/placeholders/jewelry-generic.svg';
}
 
    /**
     * Returns true if this item has a real uploaded photo
     */
    public function getHasImageAttribute(): bool
    {
        return !empty($this->primary_image) || !empty($this->gallery_images);
    }
 
    /**
     * All gallery image URLs (including primary as first)
     */
    public function getAllImagesAttribute(): array
    {
        $images = [];
 
        if ($this->primary_image) {
            $images[] = \Illuminate\Support\Facades\Storage::url($this->primary_image);
        }
 
        foreach ($this->gallery_images ?? [] as $img) {
            $url = \Illuminate\Support\Facades\Storage::url($img);
            if (!in_array($url, $images)) {
                $images[] = $url;
            }
        }
 
        return $images;
    }
    //     protected static function booted()
    // {
    //     static::saving(function ($item) {
    //         if ($item->qty <= 0) {
    //             $item->status = 'sold';
    //             $item->qty = 0;
    //         }
    //     });
    // }

}
