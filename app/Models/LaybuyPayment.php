<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaybuyPayment extends Model
{
    protected $guarded = [];

    public function laybuy(): BelongsTo
    {
        return $this->belongsTo(Laybuy::class);
    }
}