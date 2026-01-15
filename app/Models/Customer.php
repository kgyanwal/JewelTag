<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // 1. Add this

class Customer extends Model
{
    use SoftDeletes; // 2. Add this

    protected $guarded = [];
}