<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehiclePartsReplacementDetail extends Model
{
    use HasFactory;

    protected $table = 'vehicle_parts_replacement_details';

    protected $fillable = [
        'vprd_code',
        'vehicle_parts_replacement_id',
        'vehicle_part_id',
        'item_name',
        'condition_status',
        'dispatcher_notes',
    ];

    /**
     * Route model binding: pakai vprd_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'vprd_code';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function replacement()
    {
        return $this->belongsTo(VehiclePartsReplacement::class, 'vehicle_parts_replacement_id');
    }

    public function vehiclePart()
    {
        return $this->belongsTo(VehiclePart::class, 'vehicle_part_id');
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Generate vprd_code
     * Format: VPRD-{VPR_SEQ}-{ITEM_SEQ}
     */
    public static function generateCode($vprId, $sequence = null)
    {
        if (!$sequence) {
            $sequence = self::where('vehicle_parts_replacement_id', $vprId)->count() + 1;
        }
        $vprSeq = str_pad($vprId, 3, '0', STR_PAD_LEFT);
        $itemSeq = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        return "VPRD-{$vprSeq}-{$itemSeq}";
    }

    /**
     * Label status kondisi
     */
    public function getConditionLabel()
    {
        $labels = [
            'ng' => 'X (NG)',
            'kurang' => 'Kurang',
            'tidak' => 'Tidak',
        ];
        return $labels[$this->condition_status] ?? $this->condition_status;
    }

    /**
     * Warna badge kondisi
     */
    public function getConditionBadgeClass()
    {
        return match ($this->condition_status) {
            'ng', 'tidak' => 'bg-danger',
            'kurang' => 'bg-warning',
            default => 'bg-secondary',
        };
    }
}
