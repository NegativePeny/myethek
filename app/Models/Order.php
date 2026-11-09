<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'recipient_name',
        'recipient_phone',
        'merchant_id',
        'order_type',
        'customer_channel',
        'status',
        'packing_status',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'platform_fee',
        'doku_fee_amount',
        'handling_cost',
        'total_amount',
        'payment_method',
        'payment_status',
        'paid_at',
        'notes',
        'shipping_address',
        'shipping_latitude',
        'shipping_longitude',
        'picked_by',
        'picked_at',
        'packed_by',
        'packed_at',
        'package_count',
        'handed_over_at',
        'handover_code',
        'rejection_reason',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'doku_fee_amount' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'picked_at' => 'datetime',
        'packed_at' => 'datetime',
        'handed_over_at' => 'datetime',
        'package_count' => 'integer',
        'shipping_latitude' => 'float',
        'shipping_longitude' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function escrow(): HasOne
    {
        return $this->hasOne(EscrowTransaction::class);
    }

    public function picker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }
}
