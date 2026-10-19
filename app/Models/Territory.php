<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Territory extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'parent_id',
        'code',
        'name',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Territory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Territory::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    public function scopeLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeProvinces($query)
    {
        return $query->where('level', 'province');
    }

    public function scopeRegencies($query)
    {
        return $query->where('level', 'regency');
    }

    public function scopeDistricts($query)
    {
        return $query->where('level', 'district');
    }

    public function scopeVillages($query)
    {
        return $query->where('level', 'village');
    }

    /**
     * Dapatkan hirarki parent ke atas (misal: Desa -> Kecamatan -> Kabupaten -> Provinsi)
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Mendapatkan path lengkap nama wilayah (contoh: Kel. Gedog, Kec. Sananwetan, Kota Blitar)
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->name];
        $current = $this->parent;

        while ($current) {
            $parts[] = $current->name;
            $current = $current->parent;
        }

        return implode(', ', $parts);
    }
}
