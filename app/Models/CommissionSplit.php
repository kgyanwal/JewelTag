<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionSplit extends Model
{
    protected $guarded = [];

    // Link to the Sales Assistant (Prince, Brianna)
    public function salesAssistant()
    {
        return $this->belongsTo(SalesAssistant::class);
    }
}