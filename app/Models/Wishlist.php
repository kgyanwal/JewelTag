<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $guarded = [];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function productItem() { return $this->belongsTo(ProductItem::class); }
    public function salesPerson() { return $this->belongsTo(User::class, 'sales_person_id'); }
}