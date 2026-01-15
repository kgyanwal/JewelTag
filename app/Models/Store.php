<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    // THIS LINE IS MISSING. It allows all fields to be saved.
    protected $guarded = []; 

    // Relationships (if you have them)
    public function salesAssistants()
    {
        return $this->hasMany(SalesAssistant::class);
    }
}