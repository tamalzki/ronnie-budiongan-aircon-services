<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCustomerPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_customer_id',
        'part_id',
        'quantity',
        'unit_cost',
    ];

    protected $casts = [
        'quantity'  => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function dailyCustomer()
    {
        return $this->belongsTo(DailyCustomer::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
