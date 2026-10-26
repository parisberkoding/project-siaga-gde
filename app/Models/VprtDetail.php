<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VprtDetail extends Model
{
    use HasFactory;

    protected $table = 'vprt_details';

    protected $fillable = [
        'vprt_detail_code',
        'vprt_id',
        'part_name',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Route model binding: pakai vprt_detail_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'vprt_detail_code';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function transaction()
    {
        return $this->belongsTo(VehiclePartsReplacementTransaction::class, 'vprt_id');
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Generate vprt_detail_code
     */
    public static function generateCode($vprtId, $sequence = null)
    {
        if (!$sequence) {
            $sequence = self::where('vprt_id', $vprtId)->count() + 1;
        }
        $vprtSeq = str_pad($vprtId, 3, '0', STR_PAD_LEFT);
        $itemSeq = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        return "VPRTD-{$vprtSeq}-{$itemSeq}";
    }

    /**
     * Auto-calculate total_price
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->quantity && $model->unit_price && !$model->total_price) {
                $model->total_price = $model->quantity * $model->unit_price;
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty(['quantity', 'unit_price'])) {
                $model->total_price = $model->quantity * $model->unit_price;
            }
        });
    }

    /**
     * Formatted harga satuan
     */
    public function getFormattedUnitPriceAttribute()
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    /**
     * Formatted total harga
     */
    public function getFormattedTotalPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }
}
