<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleFuelBaseline extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_fuel_baselines';

    protected $fillable = [
        'vehicle_id',
        'fuel_type',
        'optimal_efficiency_km_per_liter',
        'calculation_method',
        'sample_size',
        'last_calibrated_at',
        'calibrated_by',
        'notes',
    ];

    protected $casts = [
        'optimal_efficiency_km_per_liter' => 'decimal:2',
        'sample_size' => 'integer',
        'last_calibrated_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_id');
    }

    public function calibratedBy()
    {
        return $this->belongsTo(User::class, 'calibrated_by');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeByFuelType($query, $fuelType)
    {
        return $query->where('fuel_type', $fuelType);
    }
}
