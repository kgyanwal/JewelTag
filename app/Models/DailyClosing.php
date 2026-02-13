<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
     use LogsActivity;
    protected $guarded = [];

    protected $casts = [
        'expected_data' => 'array',
        'actual_data' => 'array',
        'closing_date' => 'date',
    ];
}