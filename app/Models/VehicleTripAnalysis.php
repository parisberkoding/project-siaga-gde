<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\Transaction_Proofs;


class VehicleTripAnalysis extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_trip_analyses';

    protected $fillable = [
        'requester_id',
        'consumerial_tools_id',
        'transaction_proof_start_id',
        'odo_start',
        'fuel_volume_start',
        'filling_date_start',
        'transaction_proof_end_id',
        'odo_end',
        'fuel_volume_end',
        'filling_date_end',
        'km_difference',
        'fuel_consumed',
        'fuel_efficiency',
        'days_difference',
        'trip_category',
        'is_validated',
        'validated_by',
        'validated_at',
        'validation_status',
        'system_notes',
        'admin_notes',
    ];

    protected $casts = [
        'filling_date_start' => 'datetime',
        'filling_date_end' => 'datetime',
        'validated_at' => 'datetime',
        'is_validated' => 'boolean',
        'fuel_consumed' => 'decimal:2',
        'fuel_efficiency' => 'decimal:2',
    ];

    // Relationships
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'consumerial_tools_id');
    }

    public function transactionProofStart()
    {
        return $this->belongsTo(Transaction_Proofs::class, 'transaction_proof_start_id');
    }

    public function transactionProofEnd()
    {
        return $this->belongsTo(Transaction_Proofs::class, 'transaction_proof_end_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
    public function scopeNeedValidation($query)
    {
        return $query->where('trip_category', 'long_distance')
                     ->where('is_validated', false);
    }

    public function scopeLongDistance($query)
    {
        return $query->where('trip_category', 'long_distance');
    }

    public function scopeSuspicious($query)
    {
        return $query->where('trip_category', 'suspicious');
    }

    public function scopeNormal($query)
    {
        return $query->where('trip_category', 'normal');
    }

    // Accessors & Mutators
    public function getTripCategoryLabelAttribute()
    {
        $labels = [
            'normal' => 'Normal (< 400 KM)',
            'long_distance' => 'Perjalanan Jauh (≥ 400 KM)',
            'suspicious' => 'Mencurigakan',
        ];

        return $labels[$this->trip_category] ?? 'Unknown';
    }

    public function getValidationStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Menunggu Validasi',
            'confirmed_long_distance' => 'Terkonfirmasi - Perjalanan Jauh',
            'confirmed_normal' => 'Terkonfirmasi - Normal',
            'flagged' => 'Ditandai - Perlu Investigasi',
        ];

        return $labels[$this->validation_status] ?? 'Unknown';
    }

    public function getValidationStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'confirmed_long_distance' => 'info',
            'confirmed_normal' => 'success',
            'flagged' => 'danger',
        ];

        return $badges[$this->validation_status] ?? 'secondary';
    }

    // Helper Methods
    public function generateSystemNotes()
    {
        $notes = [];

        // Analisis jarak
        if ($this->km_difference >= 400) {
            $estimatedTrips = round($this->km_difference / 300); // Estimasi PP Jakarta-Bandung ~300km
            $notes[] = "⚠️ Jarak tempuh {$this->km_difference} KM terdeteksi (≥ 400 KM).";
            $notes[] = "📍 Estimasi: ~{$estimatedTrips}x trip PP Jakarta-Bandung.";
        }

        // Analisis efisiensi BBM
        if ($this->fuel_efficiency) {
            if ($this->fuel_efficiency < 5) {
                $notes[] = "⛽ Efisiensi BBM rendah: {$this->fuel_efficiency} KM/L (< 5 KM/L).";
            } elseif ($this->fuel_efficiency > 20) {
                $notes[] = "🔍 Efisiensi BBM tinggi: {$this->fuel_efficiency} KM/L (> 20 KM/L). Perlu verifikasi.";
            } else {
                $notes[] = "✅ Efisiensi BBM normal: {$this->fuel_efficiency} KM/L.";
            }
        }

        // Analisis durasi
        if ($this->days_difference <= 1) {
            $notes[] = "⏱️ Pengisian dalam 1 hari - kemungkinan perjalanan intensif.";
        } elseif ($this->days_difference > 30) {
            $notes[] = "📅 Gap {$this->days_difference} hari - kendaraan mungkin jarang digunakan.";
        }

        // Analisis volume BBM
        if ($this->fuel_consumed > 60) {
            $notes[] = "⛽ Volume BBM tinggi: {$this->fuel_consumed} Liter.";
        }

        return implode("\n", $notes);
    }

    public function markAsValidated($validatedBy, $status, $adminNotes = null)
    {
        $this->update([
            'is_validated' => true,
            'validated_by' => $validatedBy,
            'validated_at' => now(),
            'validation_status' => $status,
            'admin_notes' => $adminNotes,
        ]);
    }
}

