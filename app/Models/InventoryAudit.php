<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAudit extends Model
{
    // This connects to your 'inventory_audits' table
    protected $table = 'inventory_audits';

    protected $fillable = ['session_name', 'user_id', 'status'];

    // This allows you to see all the items scanned during this audit
    public function auditItems(): HasMany
    {
        return $this->hasMany(AuditItem::class, 'audit_id');
    }
}