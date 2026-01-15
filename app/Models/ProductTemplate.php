<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductTemplate extends Model
{
    protected $guarded = [];

    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }
}