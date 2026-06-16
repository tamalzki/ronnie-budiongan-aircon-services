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
        'part_id',
        'is_set',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'discount_percent',
        'discounted_cost',
        'total_cost',
        'discount_amount',
        'unit_discounts',
    ];

    protected $casts = [
        'is_set'            => 'boolean',
        'quantity_ordered'  => 'integer',
        'quantity_received' => 'integer',
        'unit_cost'         => 'decimal:2',
        'discounted_cost'   => 'decimal:2',
        'total_cost'        => 'decimal:2',
        'discount_percent'  => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'unit_discounts'  => 'array',
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

    public function part()
    {
        return $this->belongsTo(Part::class);
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

    // Whether this line item is a part (not a full product/set)
    public function getIsPartAttribute(): bool
    {
        return $this->part_id !== null;
    }

    // Display label: part name for part lines, set/model label for product lines
    public function getDisplayLabelAttribute(): string
    {
        if ($this->is_part) {
            return $this->part->name;
        }

        return $this->is_set ? $this->product->set_model_label : $this->product->model;
    }
}