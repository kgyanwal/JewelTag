<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expected_data' => 'array',
        'actual_data' => 'array',
        'closing_date' => 'date',
    ];
}