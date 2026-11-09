<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'payment_reference',
        'channel',
        'amount',
        'provider_fee',
        'status',
        'signature_verified',
        'webhook_idempotency_key',
        'paid_at',
        'expired_at',
        'settlement_status',
        'settlement_reference',
        'settled_at',
        'refund_reason',
        'refunded_by',
        'refunded_at',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_fee' => 'decimal:2',
        'signature_verified' => 'boolean',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'settled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refundedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}

