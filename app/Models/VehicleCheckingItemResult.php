<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleCheckingItemResult extends Model
{
    use HasFactory;

    protected $table = 'vehicle_checking_item_results';

    protected $fillable = [
        'vehicle_checking_detail_id',
        'vehicle_part_id',
        'status',
        'notes',
        'needs_replacement',
    ];

    protected $casts = [
        'needs_replacement' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function checkingDetail()
    {
        return $this->belongsTo(VehicleCheckingDetail::class, 'vehicle_checking_detail_id');
    }

    public function vehiclePart()
    {
        return $this->belongsTo(VehiclePart::class, 'vehicle_part_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeNeedsReplacement($query)
    {
        return $query->where('needs_replacement', true);
    }

    public function scopeOk($query)
    {
        return $query->whereIn('status', ['ok', 'baik']);
    }

    public function scopeNotOk($query)
    {
        return $query->whereNotIn('status', ['ok', 'baik']);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Label status untuk tampilan
     */
    public function getStatusLabel()
    {
        $labels = [
            'ok' => 'V (OK)',
            'ng' => 'X (NG)',
            'baik' => 'Baik',
            'cukup' => 'Cukup',
            'kurang' => 'Kurang',
            'tidak' => 'Tidak',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Warna badge berdasarkan status
     */
    public function getStatusBadgeClass()
    {
        $classes = [
            'ok' => 'bg-success',
            'baik' => 'bg-success',
            'cukup' => 'bg-warning',
            'ng' => 'bg-danger',
            'kurang' => 'bg-warning',
            'tidak' => 'bg-danger',
        ];
        return $classes[$this->status] ?? 'bg-secondary';
    }

    /**
     * Cek apakah status bermasalah
     */
    public function isProblematic()
    {
        return in_array($this->status, ['ng', 'kurang', 'tidak']);
    }
}
