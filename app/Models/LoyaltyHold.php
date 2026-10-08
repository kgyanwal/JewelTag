<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyHold extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'discount'   => 'decimal:2',
    ];
}