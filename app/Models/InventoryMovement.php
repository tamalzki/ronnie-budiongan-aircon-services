<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'reference_type',
        'reference_id',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'quantity'     => 'integer',
        'stock_before' => 'integer',
        'stock_after'  => 'integer',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'stock_in'     => '<span class="badge bg-success">↑ Stock In</span>',
            'stock_out'    => '<span class="badge bg-danger">↓ Stock Out</span>',
            'adjustment'   => '<span class="badge bg-warning text-dark">⚙ Adjustment</span>',
            'return'       => '<span class="badge bg-info text-dark">↩ Return</span>',
            default        => '<span class="badge bg-secondary">' . ucfirst($this->type) . '</span>',
        };
    }
}