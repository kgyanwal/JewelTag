<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EodAmendmentRequest extends Model
{
    protected $fillable = [
        'requested_by',
        'eod_date',
        'invoice_number',
        'amount',
        'reason',
        'status',
        'reviewed_by',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'eod_date'    => 'date',
        'reviewed_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}