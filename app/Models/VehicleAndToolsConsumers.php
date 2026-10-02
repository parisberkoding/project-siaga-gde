<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Permohonan;
use App\Models\User;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingSchedule;
use App\Models\VehiclePartsReplacement;
use App\Services\FuelBenchmarkService;

class VehicleAndToolsConsumers extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_and_tools_consumers';

    protected $fillable = [
        'consumerial_type',
        'consumerial_code',
        'consumerial_name',
        'consumerial_notes',
        'optimum_jarak_per_liter', # parameter untuk patokan efisiensi bahan bakar
        'satuan', #km, jam, dll
        'is_approved',
        'is_active',
        'approved_by',
        'created_by',
        'unit_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Gunakan consumerial_code sebagai route key agar ID tidak tampil di URL.
     */
    public function getRouteKeyName(): string
    {
        return 'consumerial_code';
    }

    /**
     * Relasi ke User (pembuat data)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke Permohonan (one to many)
     * Satu kendaraan/alat bisa punya banyak permohonan
     */
    public function permohonans()
    {
        return $this->hasMany(Permohonan::class, 'consumerial_tools_id');
    }

    /**
     * Scope: Filter berdasarkan tipe
     */
    public function scopeByType($query, $type)
    {
        return $query->where('consumerial_type', $type);
    }

    /**
     * Scope: Cari berdasarkan kode atau nama
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('consumerial_code', 'like', "%{$keyword}%")
              ->orWhere('consumerial_name', 'like', "%{$keyword}%");
        });
    }

    // Relasi ke User (approver)
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scope untuk filter approved/unapproved
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('is_approved', false);
    }

    public function fuelBaselines()
    {
        return $this->hasMany(VehicleFuelBaseline::class, 'vehicle_id');
    }

    public function checkings(){
        return $this->hasMany(VehicleChecking::class, 'vehicle_id');
    }

    /**
     * Barcode kendaraan (one to one)
     */
    public function barcode()
    {
        return $this->hasOne(VehicleBarcode::class, 'vehicle_and_tools_consumer_id');
    }

    /**
     * Detail kendaraan (one to one) — dokumentasi lengkap
     */
    public function detail()
    {
        return $this->hasOne(VehicleDetail::class, 'vehicle_and_tools_consumer_id');
    }

    /**
     * Unit bisnis kendaraan/alat (scope data antar unit).
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Scope unit bisnis: hanya kendaraan milik unit tertentu.
     * Null = tanpa scope (global / pusat) — melihat semua unit.
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return;
        }

        $query->where('vehicle_and_tools_consumers.unit_id', $unitId);
    }

    /**
     * Budget operasional (nominal_full_tank) dari detail master vehicle.
     * Sumber tunggal untuk nominal default 1x pengisian full tank.
     */
    public function getNominalFullTankAttribute(): ?float
    {
        return $this->detail?->nominal_full_tank !== null
            ? (float) $this->detail->nominal_full_tank
            : null;
    }

    /**
     * Label dropdown: "Plat - Merk" (contoh: B 1234 XYZ - Toyota Avanza)
     */
    public function getDropdownLabelAttribute(): string
    {
        $code = $this->consumerial_code ?? '';
        $name = $this->consumerial_name ?? '';
        $merk = $this->detail?->merk;
        $model = $this->detail?->model;

        $merkModel = trim(implode(' ', array_filter([$merk, $model])));

        if ($merkModel) {
            return "{$code} - {$merkModel}";
        }

        return "{$code} - {$name}";
    }

    /**
     * Benchmark BBM per unit (volume_tangki x harga region -> kelipatan 50.000).
     * null bila data teknis/harga belum tersedia.
     */
    public function getFuelBenchmarkAttribute(): ?int
    {
        return FuelBenchmarkService::benchmarkForVehicle($this);
    }

    /**
     * Rentang nominal yang diizinkan (benchmark +/- 500.000).
     */
    public function getFuelBenchmarkRangeAttribute(): array
    {
        return FuelBenchmarkService::rangeForBenchmark($this->fuel_benchmark);
    }

    /**
     * Opsi nominal (kelipatan 50.000) untuk dropdown permohonan.
     */
    public function getFuelBenchmarkOptionsAttribute(): array
    {
        return FuelBenchmarkService::optionsForBenchmark($this->fuel_benchmark);
    }
}
