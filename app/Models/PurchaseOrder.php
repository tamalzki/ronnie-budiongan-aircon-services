<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'order_date',
        'expected_delivery_date',
        'received_date',
        'subtotal',
        'tax',
        'total',
        'payment_type',
        'payment_due_date',
        'amount_paid',
        'balance',
        'payment_status',
        'status',
        'notes',
        'user_id',
        'delivery_number',
    ];

    protected $casts = [
        'order_date'             => 'date',
        'expected_delivery_date' => 'date',
        'received_date'          => 'date',
        'payment_due_date'       => 'date',
        'subtotal'               => 'decimal:2',
        'tax'                    => 'decimal:2',
        'total'                  => 'decimal:2',
        'amount_paid'            => 'decimal:2',
        'balance'                => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // All serial numbers attached to this PO
    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    // Only pending serials (entered but not yet received)
    public function pendingSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'pending');
    }

    // Only received serials (in_stock)
    public function receivedSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'in_stock');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // Total serials entered across all items in this PO
    public function getTotalSerialsEnteredAttribute(): int
    {
        return $this->serials()->count();
    }

    // Check if all items have all their serials entered
    public function getAllSerialsEnteredAttribute(): bool
    {
        foreach ($this->items as $item) {
            $entered = $this->serials()->where('product_id', $item->product_id)->count();
            if ($entered < $item->quantity_ordered) {
                return false;
            }
        }
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Auto-generate PO number
    |--------------------------------------------------------------------------
    */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (empty($po->po_number)) {
                $po->po_number = 'PO-' . date('Ymd') . '-' . str_pad(
                    static::whereDate('created_at', today())->count() + 1,
                    4, '0', STR_PAD_LEFT
                );
            }
        });
    }
}