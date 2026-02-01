<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaybuyPayment extends Model
{
    use LogsActivity;
    protected $guarded = [];

    public function laybuy(): BelongsTo
    {
        return $this->belongsTo(Laybuy::class);
    }
}