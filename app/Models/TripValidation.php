<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripValidation extends Model
{
    use HasFactory;

    protected $fillable = [
        // Transaction Links
        'transaction_proof_previous_id',
        'transaction_proof_latest_id',
        'previous_odo',
        'latest_odo',
        'diff_odo',

        // Validation Data
        'is_long_trip',
        'long_trip_notes',
        'validated_by',
        'validated_at',

        // Fuel & Cost
        'fuel_type',
        'fuel_volume',
        'fuel_cost',

        // Efficiency Analysis
        'actual_efficiency',
        'expected_efficiency',
        'efficiency_deviation_percent',

        // ✅ NEW: Baseline Override
        'baseline_override',
        'baseline_override_reason',
        'baseline_overridden_by',
        'baseline_overridden_at',

        // Premature Refuel
        'expected_range_km',
        'range_usage_percent',
        'is_premature_refuel',

        // Metadata
        'alert_level',
        'system_notes',
        'days_between_refuel',
    ];

    protected $casts = [
        'is_long_trip' => 'boolean',
        'validated_at' => 'datetime',
        'is_premature_refuel' => 'boolean',

        // Decimal casting
        'fuel_volume' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'actual_efficiency' => 'decimal:2',
        'expected_efficiency' => 'decimal:2',
        'efficiency_deviation_percent' => 'decimal:2',
        'expected_range_km' => 'decimal:2',
        'range_usage_percent' => 'decimal:2',

        // ✅ NEW
        'baseline_override' => 'decimal:2',
        'baseline_overridden_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function transactionProofPrevious()
    {
        return $this->belongsTo(Transaction_Proofs::class, 'transaction_proof_previous_id');
    }

    public function transactionProofLatest()
    {
        return $this->belongsTo(Transaction_Proofs::class, 'transaction_proof_latest_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // ✅ NEW: Relationship untuk baseline overrider
    public function baselineOverrider()
    {
        return $this->belongsTo(User::class, 'baseline_overridden_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope unit bisnis: validasi trip dari permohonan pada unit tertentu.
     * Null = tanpa scope (global / Pusat).
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return $query;
        }

        return $query->whereHas('transactionProofPrevious.permohonan', fn ($q) => $q
            ->where('permohonans.unit_id', $unitId));
    }

    public function scopeNeedsValidation($query)
    {
        return $query->whereNull('validated_at')
            ->where(function($q) {
                $q->where('diff_odo', '>=', 300)
                  ->orWhere('is_premature_refuel', true)
                  ->orWhere('efficiency_deviation_percent', '<', -20);
            });
    }

    public function scopeLongDistance($query)
    {
        return $query->where('diff_odo', '>=', 300);
    }

    public function scopeInefficient($query)
    {
        return $query->where('efficiency_deviation_percent', '<', -20);
    }

    public function scopePrematureRefuel($query)
    {
        return $query->where('is_premature_refuel', true);
    }

    public function scopeAlertLevel($query, $level)
    {
        return $query->where('alert_level', $level);
    }

    // ==================== ACCESSORS ====================

    public function getStatusBadgeAttribute()
    {
        if (!$this->validated_at) {
            return 'warning';
        }

        return $this->is_long_trip ? 'success' : 'primary';
    }

    public function getStatusLabelAttribute()
    {
        if (!$this->validated_at) {
            return 'Belum Divalidasi';
        }

        return $this->is_long_trip ? 'Perjalanan Dinas' : 'Perjalanan Normal';
    }

    public function getAlertLevelBadgeAttribute()
    {
        $badges = [
            'normal' => 'success',
            'warning' => 'warning',
            'critical' => 'danger',
        ];

        return $badges[$this->alert_level] ?? 'secondary';
    }

    public function getAlertLevelLabelAttribute()
    {
        $labels = [
            'normal' => 'Normal',
            'warning' => 'Perlu Perhatian',
            'critical' => 'Kritis',
        ];

        return $labels[$this->alert_level] ?? 'Unknown';
    }

    public function getEfficiencyStatusAttribute()
    {
        if (!$this->efficiency_deviation_percent) {
            return 'no_data';
        }

        if ($this->efficiency_deviation_percent >= -10) {
            return 'good';
        } elseif ($this->efficiency_deviation_percent >= -20) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    public function getEfficiencyStatusBadgeAttribute()
    {
        $badges = [
            'good' => 'success',
            'fair' => 'warning',
            'poor' => 'danger',
            'no_data' => 'secondary',
        ];

        return $badges[$this->efficiency_status] ?? 'secondary';
    }

    // ✅ NEW: Accessor untuk info sumber baseline
    public function getBaselineSourceAttribute()
    {
        if ($this->baseline_override) {
            return 'Disesuaikan Admin';
        }

        return 'Standar Sistem';
    }

    // ==================== METHODS ====================

    /**
     * Calculate semua metrics efficiency & premature refuel
     */
    public function calculateMetrics()
    {
        $prevProof = $this->transactionProofPrevious;
        $latestProof = $this->transactionProofLatest;

        if (!$prevProof || !$latestProof) {
            return false;
        }

        $permohonan = $prevProof->permohonan;
        if (!$permohonan) {
            return false;
        }

        // 1. Basic data
        $fuelType = $permohonan->gasoline_type;
        $fuelVolume = $prevProof->fuel_volume;
        $fuelCost = $permohonan->bill_amounts;
        $kmDiff = $this->diff_odo;

        // 2. Actual efficiency
        $actualEfficiency = $fuelVolume > 0 ? $kmDiff / $fuelVolume : 0;

        // 3. ✅ NEW LOGIC: Get expected efficiency
        if ($this->baseline_override) {
            // Pakai patokan yang sudah disesuaikan
            $expectedEfficiency = $this->baseline_override;
        } else {
            // Pakai standar default berdasarkan jenis BBM
            $expectedEfficiency = $this->getDefaultBaselineByFuelType($fuelType);
        }

        // 4. Calculate deviation
        $deviation = $expectedEfficiency > 0
            ? (($actualEfficiency - $expectedEfficiency) / $expectedEfficiency) * 100
            : 0;

        // 5. Calculate expected range & usage
        $expectedRange = $fuelVolume * $expectedEfficiency;
        $rangeUsage = $expectedRange > 0 ? ($kmDiff / $expectedRange) * 100 : 0;

        // 6. Determine premature refuel (threshold: < 60%)
        $isPremature = $rangeUsage < 60 && $rangeUsage > 0;

        // 7. Determine alert level
        $alertLevel = $this->determineAlertLevel($deviation, $isPremature, $kmDiff);

        // 8. Days between refuel
        $daysBetween = $prevProof->purchase_datetime->diffInDays($latestProof->purchase_datetime);

        // 9. Generate system notes
        $systemNotes = $this->generateSystemNotes(
            $kmDiff,
            $actualEfficiency,
            $expectedEfficiency,
            $deviation,
            $rangeUsage,
            $isPremature,
            $daysBetween
        );

        // 10. Update record
        $this->update([
            'fuel_type' => $fuelType,
            'fuel_volume' => $fuelVolume,
            'fuel_cost' => $fuelCost,
            'actual_efficiency' => round($actualEfficiency, 2),
            'expected_efficiency' => round($expectedEfficiency, 2),
            'efficiency_deviation_percent' => round($deviation, 2),
            'expected_range_km' => round($expectedRange, 2),
            'range_usage_percent' => round($rangeUsage, 2),
            'is_premature_refuel' => $isPremature,
            'alert_level' => $alertLevel,
            'system_notes' => $systemNotes,
            'days_between_refuel' => $daysBetween,
        ]);

        return true;
    }

    /**
     * et default baseline efficiency by fuel type
     */
    private function getDefaultBaselineByFuelType($fuelType)
    {
        $defaults = [
            'Pertamax' => 15.0,          // RON 92
            'Pertamina Dex' => 7.0,      // CN 53
        ];

        return $defaults[$fuelType] ?? 12.0; // Fallback untuk tipe lain
    }

    /**
     * Determine alert level based on metrics
     */
    private function determineAlertLevel($deviation, $isPremature, $kmDiff)
    {
        // Critical: Kombinasi inefficient + premature OR super inefficient OR suspicious long distance
        if (($deviation < -30 && $isPremature) || $deviation < -40 || ($kmDiff > 800 && $deviation < -25)) {
            return 'critical';
        }

        // Warning: Moderate inefficiency OR premature refuel OR long distance
        if ($deviation < -20 || $isPremature || $kmDiff >= 300) {
            return 'warning';
        }

        // Normal
        return 'normal';
    }

    /**
     * Generate system notes automatically
     */
    private function generateSystemNotes($kmDiff, $actual, $expected, $deviation, $rangeUsage, $isPremature, $daysBetween)
    {
        $notes = [];

        // 1. Distance analysis
        if ($kmDiff >= 400) {
            $notes[] = "Jarak tempuh tinggi: " . number_format($kmDiff, 0) . " KM (≥400 KM)";
        } elseif ($kmDiff >= 300) {
            $notes[] = "Jarak tempuh: " . number_format($kmDiff, 0) . " KM (perlu validasi)";
        }

        // 2. Efficiency analysis
        if ($deviation < -30) {
            $notes[] = "Efisiensi BBM sangat rendah: " . round($actual, 1) . " km/L (patokan " . round($expected, 1) . " km/L) - Selisih " . round($deviation, 1) . "%";
        } elseif ($deviation < -20) {
            $notes[] = "Efisiensi BBM di bawah patokan: " . round($actual, 1) . " km/L (patokan " . round($expected, 1) . " km/L) - Selisih " . round($deviation, 1) . "%";
        } elseif ($deviation > 10) {
            $notes[] = "Efisiensi BBM di atas patokan: " . round($actual, 1) . " km/L (patokan " . round($expected, 1) . " km/L) - Perlu verifikasi data";
        } else {
            $notes[] = "Efisiensi BBM normal: " . round($actual, 1) . " km/L";
        }

        // 3. Premature refuel analysis
        if ($isPremature) {
            $notes[] = "Pengisian terlalu cepat: Baru " . round($rangeUsage, 1) . "% dari jarak optimal (" . number_format($kmDiff, 0) . " KM dari optimal " . number_format($expected * $this->fuel_volume, 0) . " KM)";
        } elseif ($rangeUsage > 100) {
            $notes[] = "ℹPemakaian melebihi jarak optimal: " . round($rangeUsage, 1) . "% (mungkin ada perjalanan tambahan)";
        }

        // 4. Days analysis
        if ($daysBetween <= 1) {
            $notes[] = "Pengisian dalam 1 hari - kemungkinan perjalanan intensif";
        } elseif ($daysBetween > 30) {
            $notes[] = "Gap " . $daysBetween . " hari - kendaraan jarang digunakan";
        }

        return implode("\n", $notes);
    }
}
