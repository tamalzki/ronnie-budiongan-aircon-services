<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'installment_number',
        'amount',
        'amount_paid',
        'due_date',
        'paid_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}