<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'service_type',
        'other_service',
        'amount',
        'status',
        'service_date',
        'notes',
        'parts_included_in_price',
        'user_id',
    ];

    protected $casts = [
        'amount'                   => 'decimal:2',
        'service_date'             => 'date',
        'parts_included_in_price'  => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parts()
    {
        return $this->hasMany(DailyCustomerPart::class);
    }

    public function getServiceLabelAttribute(): string
    {
        return $this->service_type === 'Others' && $this->other_service
            ? $this->other_service
            : $this->service_type;
    }
}
