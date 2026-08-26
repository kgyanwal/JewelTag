<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterAuditLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(
        string $action,
        ?string $fieldLabel = null,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $tenantId = null,
        ?string $tenantName = null,
        string $severity = 'info',
    ): void {
        static::create([
            'actor_id'    => auth()->id(),
            'actor_name'  => auth()->user()?->name ?? 'System',
            'tenant_id'   => $tenantId,
            'tenant_name' => $tenantName,
            'action'      => $action,
            'field_label' => $fieldLabel,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
            'severity'    => $severity,
            'ip_address'  => request()?->ip(),
        ]);
    }
}