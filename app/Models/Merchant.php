<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'territory_id',
        'tier',
        'store_name',
        'slug',
        'phone',
        'address',
        'latitude',
        'longitude',
        'status',
        'is_open',
        'min_order_amount',
        'self_delivery_active',
        'delivery_radius_km',
        'opening_time',
        'closing_time',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_open' => 'boolean',
        'self_delivery_active' => 'boolean',
        'min_order_amount' => 'decimal:2',
        'delivery_radius_km' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(MerchantInventory::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    public function scopeAvailableForOrders($query)
    {
        return $query->where('status', 'active')->where('is_open', true);
    }

    public function scopeTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function isSubAgent(): bool
    {
        return $this->tier === 'sub_agent';
    }

    public function isAgent(): bool
    {
        return $this->tier === 'agent';
    }

    public function isDistributor(): bool
    {
        return $this->tier === 'distributor';
    }

    public function isCenter(): bool
    {
        return $this->tier === 'center';
    }
}
