<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand_id',
        'model',
        'unit_type',
        'paired_product_id',
        'hp',
        'supplier_id',
        'description',
        'cost',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost'      => 'decimal:2',
        'price'     => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // ── Set pairing (indoor row points to its outdoor counterpart) ────────

    public function pairedProduct()
    {
        return $this->belongsTo(Product::class, 'paired_product_id');
    }

    public function pairedIndoorUnits()
    {
        return $this->hasMany(Product::class, 'paired_product_id');
    }

    // True when this product is the indoor unit of a set (sold as one price)
    public function getIsSetPrimaryAttribute(): bool
    {
        return $this->unit_type === 'indoor' && $this->paired_product_id !== null;
    }

    // "FTKZ25WVM / RKZ25WVM" — both models of the set
    public function getSetModelLabelAttribute(): string
    {
        if ($this->is_set_primary && $this->pairedProduct) {
            return $this->model . ' / ' . $this->pairedProduct->model;
        }
        return $this->model;
    }

    // ── Serial relationships ──────────────────────────────────────────────

    public function serials()
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function pendingSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'pending');
    }

    public function inStockSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'in_stock');
    }

    public function soldSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'sold');
    }

    public function defectiveSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'defective');
    }

    public function returnedSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'returned');
    }

    public function lostSerials()
    {
        return $this->hasMany(ProductSerial::class)->where('status', 'lost');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors — Stock counts derived from product_serials
    |--------------------------------------------------------------------------
    */

    // Total units ever received for this model
    public function getTotalUnitsAttribute(): int
    {
        return $this->serials()->count();
    }

    // Units currently available to sell
    public function getStockCountAttribute(): int
    {
        return $this->inStockSerials()->count();
    }

    // Units pending (entered in PO but not yet received)
    public function getPendingCountAttribute(): int
    {
        return $this->pendingSerials()->count();
    }

    // Units sold
    public function getSoldCountAttribute(): int
    {
        return $this->soldSerials()->count();
    }

    // Whether any units are available
    public function getInStockAttribute(): bool
    {
        return $this->inStockSerials()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors — Display
    |--------------------------------------------------------------------------
    */

    public function getDisplayModelAttribute(): string
    {
        if ($this->unit_type) {
            return $this->model . ' (' . ucfirst($this->unit_type) . ')';
        }
        return $this->model;
    }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->brand->name ?? null,
            $this->model       ?? null,
            $this->unit_type   ? ucfirst($this->unit_type) : null,
        ]);
        return implode(' · ', $parts);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeIndoor($query)
    {
        return $query->where('unit_type', 'indoor');
    }

    public function scopeOutdoor($query)
    {
        return $query->where('unit_type', 'outdoor');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithStock($query)
    {
        // Products that have at least one in_stock serial
        return $query->whereHas('serials', fn($q) => $q->where('status', 'in_stock'));
    }

    public function scopeSellable($query)
    {
        // Active, has a price, and has stock
        return $query->where('is_active', true)
                     ->where('price', '>', 0)
                     ->whereHas('serials', fn($q) => $q->where('status', 'in_stock'));
    }
}