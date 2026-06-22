<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketMessage extends Model
{
    protected $connection = 'mysql';
    protected $fillable = [
        'ticket_id',
        'sender_type',
        'sender_name',
        'message',
        'attachments',
        'is_internal_note',
    ];

    protected $casts = [
        'attachments'      => 'array',
        'is_internal_note' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }
}