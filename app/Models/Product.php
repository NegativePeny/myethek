<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'slug',
        'unit_name',
        'base_price',
        'description',
        'image',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tierPrices(): HasMany
    {
        return $this->hasMany(TierPrice::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(MerchantInventory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Ambil harga kulakan untuk tier tertentu
     */
    public function getWholesalePriceFor(string $buyerTier): ?float
    {
        $tier = $this->tierPrices()->where('buyer_tier', $buyerTier)->first();
        return $tier ? (float) $tier->wholesale_price : (float) $this->base_price;
    }
}
