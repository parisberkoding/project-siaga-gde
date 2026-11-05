<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class VehicleChecking extends Model
{
    use HasFactory;

    protected $table = 'vehicle_checkings';

    protected $fillable = [
        'checking_code',
        'vehicle_id',
        'nopol',
        'revision',
        'period_start',
        'period_end',
        'generated_by',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'revision' => 'integer',
    ];

    /**
     * Route model binding: pakai checking_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'checking_code';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function details()
    {
        return $this->hasMany(VehicleCheckingDetail::class, 'vehicle_checking_id');
    }

    public function routineDetails()
    {
        return $this->hasMany(VehicleCheckingDetail::class, 'vehicle_checking_id')
                    ->where('type', 'routine');
    }

    public function bodyDetails()
    {
        return $this->hasMany(VehicleCheckingDetail::class, 'vehicle_checking_id')
                    ->where('type', 'body');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrentPeriod($query)
    {
        $now = Carbon::now();
        return $query->where('period_start', $now->copy()->startOfMonth()->toDateString())
                     ->where('period_end', $now->copy()->endOfMonth()->toDateString());
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope unit bisnis: periode pengecekan milik kendaraan pada unit tertentu.
     * Null = tanpa scope (global / Pusat).
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return $query;
        }

        return $query->whereHas('vehicle', fn ($q) => $q
            ->where('vehicle_and_tools_consumers.unit_id', $unitId));
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Generate checking_code dari nopol dan periode
     */
    public static function generateCode($nopol, Carbon $date = null)
    {
        $date = $date ?: Carbon::now();
        // Hapus spasi dari nopol
        $cleanNopol = str_replace(' ', '', $nopol);
        return 'CHK-' . $cleanNopol . '-' . $date->format('Y-m');
    }

    /**
     * Cek apakah periode ini sudah di-generate
     */
    public static function periodExists($vehicleId, Carbon $date = null)
    {
        $date = $date ?: Carbon::now();
        return self::where('vehicle_id', $vehicleId)
                   ->where('period_start', $date->copy()->startOfMonth()->toDateString())
                   ->exists();
    }

    /**
     * Hitung total pengecekan di periode ini
     */
    public function getTotalChecksAttribute()
    {
        return $this->details()->count();
    }

    /**
     * Nama periode: "Februari 2025"
     */
    public function getPeriodNameAttribute()
    {
        return $this->period_start->translatedFormat('F Y');
    }

    /**
     * Jadwal pengecekan kendaraan ini (via vehicle_id).
     * VehicleCheckingSchedule direlasikan ke kendaraan, bukan ke header checking.
     */
    public function checkingSchedules()
    {
        return $this->hasMany(VehicleCheckingSchedule::class, 'vehicle_id', 'vehicle_id');
    }
}
