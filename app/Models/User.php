<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'customer_type',
        'b2b_verified_at',
        'b2b_store_name',
        'territory_id',
        'address',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'b2b_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function merchant(): HasOne
    {
        return $this->hasOne(Merchant::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'driver_id');
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isB2BCustomer(): bool
    {
        return $this->customer_type === 'b2b' && $this->b2b_verified_at !== null;
    }

    public function isMerchant(): bool
    {
        return in_array($this->role, ['sub_agent', 'agent', 'distributor', 'center', 'warehouse', 'admin']);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isFinance(): bool
    {
        return in_array($this->role, ['finance', 'superadmin']);
    }

    public function isWarehouse(): bool
    {
        return in_array($this->role, ['warehouse', 'admin', 'superadmin']);
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }
}
