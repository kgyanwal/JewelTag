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
    $changes = null;

    if ($action === 'Updated') {
        // Get only the fields that were modified
        $dirty = $model->getDirty();
        $changesArray = [];

        foreach ($dirty as $key => $newValue) {
            // Skip timestamps
            if (in_array($key, ['updated_at', 'created_at'])) continue;

            $oldValue = $model->getOriginal($key);
            $changesArray[$key] = [
                'from' => $oldValue,
                'to' => $newValue
            ];
        }
        $changes = count($changesArray) > 0 ? json_encode($changesArray) : null;
    }

    \App\Models\ActivityLog::create([
        'user_id' => auth()->id() ?? 1,
        'action' => $action,
        'module' => class_basename($model),
        'identifier' => $model->invoice_number ?? $model->barcode ?? $model->id ?? 'System',
        'changes' => $changes,
        'url' => '/' . request()->path(), // Adding the URL here too!
        'ip_address' => request()->ip(),
    ]);
}
}