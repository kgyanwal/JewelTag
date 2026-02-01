<?php
namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(fn ($model) => static::logActivity($model, 'Created'));
        static::updated(fn ($model) => static::logActivity($model, 'Updated'));
        static::deleted(fn ($model) => static::logActivity($model, 'Deleted'));
    }

    protected static function logActivity($model, $action)
    {
        ActivityLog::create([
            'user_id' => Auth::id() ?? 1,
            'action' => $action,
            'module' => class_basename($model),
            // Uses your custom identifiers
            'identifier' => $model->invoice_number ?? $model->barcode ?? $model->id,
            'changes' => $action === 'Updated' ? json_encode($model->getDirty()) : null,
            'ip_address' => request()->ip(),
        ]);
    }
}