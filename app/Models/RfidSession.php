<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RfidSession extends Model
{
    protected $fillable = [
        'session_name', 'session_type', 'device_type',
        'device_ip', 'device_port', 'status',
        'total_scanned', 'matched', 'unmatched',
        'scan_results', 'started_at', 'completed_at', 'user_id',
    ];

    protected $casts = [
        'scan_results' => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(RfidScanLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}