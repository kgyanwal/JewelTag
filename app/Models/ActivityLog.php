<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\MassPrunable; // 1. Import this

class ActivityLog extends Model
{
    use MassPrunable; // 2. Enable the feature

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 3. Define the cleanup logic
    public function prunable()
    {
        // Automatically delete logs older than 12 days
        return static::where('created_at', '<=', now()->subDays(12));
    }
}