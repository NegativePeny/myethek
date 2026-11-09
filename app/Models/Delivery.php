<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'delivery_type',
        'courier_provider',
        'courier_job_id',
        'distance_km',
        'fee',
        'driver_id',
        'pod_image_path',
        'receiver_name',
        'completion_otp',
        'otp_hash',
        'otp_expires_at',
        'otp_attempts',
        'status',
        'rejection_reason',
        'assigned_at',
        'accepted_at',
        'picked_up_at',
        'delivered_at',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'fee' => 'decimal:2',
        'otp_attempts' => 'integer',
        'otp_expires_at' => 'datetime',
        'assigned_at' => 'datetime',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
