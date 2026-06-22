<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportKbArticle extends Model
{
    protected $connection = 'mysql';
    protected $fillable = [
        'title',
        'slug',
        'category',
        'body',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}