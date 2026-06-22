<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportCannedResponse extends Model
{
    protected $connection = 'mysql';
    protected $fillable = [
        'title',
        'category',
        'body',
    ];
}