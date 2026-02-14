<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_name',
        'customer_contact',
        'customer_address',
        'sale_type',
        'subtotal',
        'discount',
        'total',
        'payment_type',
        'installment_months',
        'installment_amount',
        'paid_amount',
        'balance',
        'status',
        'sale_date',
        'user_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'sale_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function installmentPayments()
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->invoice_number)) {
                $sale->invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}