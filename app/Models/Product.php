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
        'cost_price',
        'base_price',
        'b2b_price',
        'description',
        'image',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'base_price' => 'decimal:2',
        'b2b_price' => 'decimal:2',
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
     * Ambil harga grosir B2B koperasi (B2B-002)
     */
    public function getB2bEffectivePriceAttribute(): float
    {
        if ($this->b2b_price && $this->b2b_price > 0) {
            return (float) $this->b2b_price;
        }
        $subAgentTier = $this->tierPrices->where('buyer_tier', 'sub_agent')->first();
        if ($subAgentTier) {
            return (float) $subAgentTier->wholesale_price;
        }
        return round((float) $this->base_price * 0.92, 0);
    }

    public function getEffectiveB2bPrice(): float
    {
        return $this->b2b_effective_price;
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
