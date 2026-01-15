<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesAssistant extends Model
{
    protected $guarded = []; // Allows saving data

    // This is the missing part causing the error
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}