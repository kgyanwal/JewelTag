<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Add this
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use LogsActivity;
    use SoftDeletes; // 2. Add this

    protected $guarded = [];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
    protected static function booted()
{
    static::saving(function ($customer) {
        if (empty($customer->country)) {
            $customer->country = 'USA';
        }
    });
}
}