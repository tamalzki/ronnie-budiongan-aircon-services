<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'payment_method',
        'installment_months',
        'installment_amount',
        'paid_amount',
        'balance',
        'status',
        'notes',
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
            if (! empty($sale->invoice_number)) {
                return;
            }

            $sale->invoice_number = static::nextInvoiceNumber();
        });
    }

    /**
     * Next same-day invoice sequence under a short DB lock to avoid duplicate numbers when sales are created concurrently.
     */
    public static function nextInvoiceNumber(): string
    {
        return DB::transaction(function () {
            $prefix = 'INV-' . now()->format('Ymd') . '-';

            $last = static::query()
                ->where('invoice_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('invoice_number');

            $seq = 1;
            if ($last && preg_match('/(\d{4})$/', $last, $m)) {
                $seq = (int) $m[1] + 1;
            }

            return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        });
    }
}