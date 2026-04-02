<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laybuy extends Model
{
    use LogsActivity;

    protected $fillable = [
        'laybuy_no', 'customer_id', 'sale_id', 'store_id',
        'total_amount', 'amount_paid', 'balance_due',
        'status', 'sales_person', 'staff_list',
        'start_date', 'last_paid_date', 'due_date', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'amount_paid'  => 'decimal:2',
        'balance_due'  => 'decimal:2',
        'start_date'   => 'date',
        'last_paid_date' => 'date',
        'due_date'     => 'date',
        'staff_list'   => 'array', // 🚀 Handles multiple staff automatically
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }

    public function sale(): BelongsTo { 
        return $this->belongsTo(Sale::class)->with('items.productItem', 'payments'); 
    }

    public function payments(): HasMany { 
        return $this->hasMany(LaybuyPayment::class)->orderBy('created_at', 'desc'); 
    }

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}