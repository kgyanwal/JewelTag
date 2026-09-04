<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseNote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_published'  => 'boolean',
        'email_sent'    => 'boolean',
        'sms_sent'      => 'boolean',
        'published_at'  => 'datetime',
        'expires_at'    => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function typeStyle(string $type): array
    {
        return match ($type) {
            'feature'      => ['label' => '✨ New Feature',  'color' => 'success', 'bg' => '#EAF6EF', 'border' => '#0F7A5C', 'text' => '#0B3D3C'],
            'fix'          => ['label' => '🔧 Bug Fix',      'color' => 'warning', 'bg' => '#FBF3E2', 'border' => '#C9A24B', 'text' => '#5A4419'],
            'improvement'  => ['label' => '⚡ Improvement',   'color' => 'info',    'bg' => '#EEF3F2', 'border' => '#3D6B63', 'text' => '#0B3D3C'],
            default        => ['label' => '📢 Announcement', 'color' => 'gray',    'bg' => '#F3F4F6', 'border' => '#6B7280', 'text' => '#1F2937'],
        };
    }
}