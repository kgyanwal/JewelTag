<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintLog extends Model
{
    protected $fillable = [
        'product_item_id', 'barcode', 'rfid_code', 'print_type',
        'printer_ip', 'user_id', 'user_name', 'status', 'error_message',
    ];

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class);
    }
}