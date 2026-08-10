<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterGasoline extends Model
{
    use HasFactory;

    protected $table = 'master_gasolines';

    protected $fillable = [
        'province',
        'province_slug',
        'fuel_type',
        'price_per_liter',
        'availability',
        'price_date',
        'synced_at',
    ];

    protected $casts = [
        'price_per_liter' => 'integer',
        'price_date'      => 'datetime',
        'synced_at'       => 'datetime',
    ];

    /**
     * Harga per liter terbaru untuk region + produk tertentu.
     */
    public static function latestPrice(string $provinceSlug, string $fuelType): ?int
    {
        $price = static::where('province_slug', $provinceSlug)
            ->where('fuel_type', $fuelType)
            ->value('price_per_liter');

        return $price !== null ? (int) $price : null;
    }
}
