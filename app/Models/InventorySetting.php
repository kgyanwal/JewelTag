<?php
namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class InventorySetting extends Model
{
     use LogsActivity;
    protected $fillable = ['key', 'value'];
    protected $casts = ['value' => 'array'];
}