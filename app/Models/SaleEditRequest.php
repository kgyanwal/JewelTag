<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleEditRequest extends Model
{
    protected $fillable = ['sale_id', 'user_id', 'proposed_changes', 'status', 'approved_by'];

protected $casts = [
    'proposed_changes' => 'array', // Crucial for handling JSON data
];

public function sale() { return $this->belongsTo(Sale::class); }
public function user() { return $this->belongsTo(User::class); }
}
