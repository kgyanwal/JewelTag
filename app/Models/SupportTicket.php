<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $connection = 'mysql';
    protected $fillable = [
        'tenant_id',
        'store_name',
        'subject',
        'category',
        'priority',
        'status',
        'created_by_name',
        'created_by_user_id',
        'assigned_to',
        'last_reply_at',
        'sla_due_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'sla_due_at'    => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}