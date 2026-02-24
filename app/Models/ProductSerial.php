<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_order_id',
        'sale_id',
        'sale_item_id',
        'serial_number',
        'status',
        'received_date',
        'notes',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInStock($query)
    {
        return $query->where('status', 'in_stock');
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeDefective($query)
    {
        return $query->where('status', 'defective');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeAvailable($query)
    {
        // Available to sell = in_stock only
        return $query->where('status', 'in_stock');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending'   => '<span class="badge" style="background:#fff3cd;color:#856404;border:1px solid #ffc107;">⏳ Pending</span>',
            'in_stock'  => '<span class="badge" style="background:#d1e7dd;color:#0a3622;border:1px solid #86efac;">✅ In Stock</span>',
            'sold'      => '<span class="badge" style="background:#cfe2ff;color:#084298;border:1px solid #93c5fd;">🛒 Sold</span>',
            'returned'  => '<span class="badge" style="background:#e2e3e5;color:#41464b;border:1px solid #adb5bd;">↩️ Returned</span>',
            'defective' => '<span class="badge" style="background:#f8d7da;color:#842029;border:1px solid #f1aeb5;">⚠️ Defective</span>',
            'lost'      => '<span class="badge" style="background:#f8d7da;color:#842029;border:1px solid #f1aeb5;">❌ Lost</span>',
            default     => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'in_stock';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }
}