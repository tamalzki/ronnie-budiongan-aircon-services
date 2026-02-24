<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'discount_percent',
        'discounted_cost',
        'total_cost',
    ];

    protected $casts = [
        'quantity_ordered'  => 'integer',
        'quantity_received' => 'integer',
        'unit_cost'         => 'decimal:2',
        'discounted_cost'   => 'decimal:2',
        'total_cost'        => 'decimal:2',
        'discount_percent'  => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // All serials for this specific item (same product + same PO)
    public function serials()
    {
        return $this->hasMany(ProductSerial::class, 'purchase_order_id', 'purchase_order_id')
                    ->where('product_id', $this->product_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // How many serials have been entered for this line item
    public function getSerialsEnteredCountAttribute(): int
    {
        return ProductSerial::where('purchase_order_id', $this->purchase_order_id)
                            ->where('product_id', $this->product_id)
                            ->count();
    }

    // How many serials are still missing
    public function getSerialsMissingCountAttribute(): int
    {
        return max(0, $this->quantity_ordered - $this->serials_entered_count);
    }

    // Whether all serials have been entered for this line item
    public function getAllSerialsEnteredAttribute(): bool
    {
        return $this->serials_entered_count >= $this->quantity_ordered;
    }

    // Quantity still to be received
    public function getRemainingQuantityAttribute(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }
}