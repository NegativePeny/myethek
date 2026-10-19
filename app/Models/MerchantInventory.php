<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'product_id',
        'stock',
        'custom_price',
        'is_available',
    ];

    protected $casts = [
        'stock' => 'integer',
        'custom_price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->custom_price && $this->custom_price > 0) {
            return (float) $this->custom_price;
        }
        return (float) $this->product->base_price;
    }
}
