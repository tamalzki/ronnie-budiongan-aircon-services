<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_id',
        'description',
        'cost',
        'is_active',
    ];

    protected $casts = [
        'cost'      => 'decimal:2',
        'is_active' => 'boolean',
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

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function dailyCustomerParts()
    {
        return $this->hasMany(DailyCustomerPart::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    // Current stock, derived from inventory movements (stock_in/return add, stock_out subtracts)
    public function getStockQuantityAttribute(): int
    {
        return (int) $this->movements()
            ->selectRaw("COALESCE(SUM(CASE WHEN type IN ('stock_in', 'return') THEN quantity WHEN type = 'stock_out' THEN -quantity ELSE 0 END), 0) as total")
            ->value('total');
    }

    // Label for the model/set this part is linked to, if any
    public function getLinkedModelLabelAttribute(): ?string
    {
        if (! $this->product) {
            return null;
        }

        return $this->product->is_set_primary
            ? $this->product->set_model_label
            : $this->product->display_model;
    }
}
