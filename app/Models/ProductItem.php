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
        'shopify_product_id',
'shopify_inventory_item_id',
'product_videos',

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
        'product_videos' => 'array',
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
        // Handle array (Filament FileUpload sometimes stores as array)
        $primary = is_array($this->primary_image)
            ? (array_values($this->primary_image)[0] ?? null)
            : $this->primary_image;
 
        if ($primary) {
            // Use url() instead of Storage::url() to avoid misconfigured disk URL
            return url('storage/' . $primary);
        }
 
        // Fallback to first gallery image
        $gallery = $this->gallery_images ?? [];
        if (is_array($gallery) && !empty($gallery[0])) {
            $first = is_array($gallery[0]) ? ($gallery[0][0] ?? null) : $gallery[0];
            if ($first) {
                return url('storage/' . $first);
            }
        }
    } catch (\Exception $e) {
        // fall through to placeholder
    }
 
    // Category-based placeholder
    $category     = strtolower($this->category ?? '');
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
 
public function getHasImageAttribute(): bool
{
    $primary = is_array($this->primary_image)
        ? (array_values($this->primary_image)[0] ?? null)
        : $this->primary_image;
 
    return !empty($primary) || !empty($this->gallery_images);
}
 
public function getAllImagesAttribute(): array
{
    $images = [];
 
    $primary = is_array($this->primary_image)
        ? (array_values($this->primary_image)[0] ?? null)
        : $this->primary_image;
 
    if ($primary) {
        $images[] = url('storage/' . $primary);
    }
 
    foreach ($this->gallery_images ?? [] as $img) {
        if (!$img) continue;
        $imgPath = is_array($img) ? ($img[0] ?? null) : $img;
        if (!$imgPath) continue;
        $imgUrl = url('storage/' . $imgPath);
        if (!in_array($imgUrl, $images)) {
            $images[] = $imgUrl;
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
