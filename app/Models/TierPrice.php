<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TierPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'buyer_tier',
        'wholesale_price',
        'moq',
    ];

    protected $casts = [
        'wholesale_price' => 'decimal:2',
        'moq' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
