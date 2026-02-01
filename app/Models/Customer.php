<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Add this

class Customer extends Model
{
    use LogsActivity;
    use SoftDeletes; // 2. Add this

    protected $guarded = [];
}