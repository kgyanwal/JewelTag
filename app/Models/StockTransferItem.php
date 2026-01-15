<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $guarded = [];

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }
}