<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EscrowTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gross_amount',
        'merchant_portion',
        'driver_portion',
        'platform_portion',
        'status',
        'released_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'merchant_portion' => 'decimal:2',
        'driver_portion' => 'decimal:2',
        'platform_portion' => 'decimal:2',
        'released_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
