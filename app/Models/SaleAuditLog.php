<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleAuditLog extends Model
{
    protected $fillable = [
        'sale_id', 'user_id', 'user_name',
        'field_label', 'old_value', 'new_value', 'severity',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}