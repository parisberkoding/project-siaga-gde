<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\VehicleChecking;
use App\Models\VehicleCheckingItemResult;
use App\Models\VehiclePartsReplacement;
use App\Models\DamagePhoto;
use App\Models\User;

class VehicleCheckingDetail extends Model
{
    use HasFactory;

    protected $table = 'vehicle_checking_details';

    protected $fillable = [
        'checking_detail_code',
        'vehicle_checking_id',
        'type',
        'sub_type',
        'dispatcher_id',
        'current_driver_id',
        'checked_at',
        'driver_acknowledged',
        'driver_acknowledged_at',
        'notes',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'driver_acknowledged' => 'boolean',
        'driver_acknowledged_at' => 'datetime',
    ];

    /**
     * Route model binding: pakai checking_detail_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'checking_detail_code';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function vehicleChecking()
    {
        return $this->belongsTo(VehicleChecking::class, 'vehicle_checking_id');
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function currentDriver()
    {
        return $this->belongsTo(User::class, 'current_driver_id');
    }

    public function itemResults()
    {
        return $this->hasMany(VehicleCheckingItemResult::class, 'vehicle_checking_detail_id');
    }

    public function partsReplacement()
    {
        return $this->hasOne(VehiclePartsReplacement::class, 'vehicle_checking_detail_id');
    }

    public function damagePhotos()
    {
        return $this->hasMany(DamagePhoto::class, 'vehicle_checking_detail_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeRoutine($query)
    {
        return $query->where('type', 'routine');
    }

    public function scopeBody($query)
    {
        return $query->where('type', 'body');
    }

    public function scopeUnacknowledged($query)
    {
        return $query->where('driver_acknowledged', false);
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('driver_acknowledged', true);
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('current_driver_id', $driverId);
    }

    public function scopeForDispatcher($query, $dispatcherId)
    {
        return $query->where('dispatcher_id', $dispatcherId);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Generate checking_detail_code
     * Format: CHK-{NOPOL}-{YYYY}-{MM}-{SEQ}
     */
    public static function generateCode($vehicleChecking)
    {
        $lastSeq = self::where('vehicle_checking_id', $vehicleChecking->id)->count() + 1;
        $seq = str_pad($lastSeq, 3, '0', STR_PAD_LEFT);
        return $vehicleChecking->checking_code . '-' . $seq;
    }

    /**
     * Driver acknowledge pengecekan
     */
    public function acknowledge()
    {
        $this->update([
            'driver_acknowledged' => true,
            'driver_acknowledged_at' => now(),
        ]);
    }

    /**
     * Cek apakah ada item yang perlu diganti
     */
    public function hasReplacementNeeded()
    {
        return $this->itemResults()->where('needs_replacement', true)->exists();
    }

    /**
     * Get items yang perlu diganti
     */
    public function getReplacementItems()
    {
        return $this->itemResults()->where('needs_replacement', true)->with('vehiclePart')->get();
    }

    /**
     * Hitung jumlah item OK vs NG
     */
    public function getOkCountAttribute()
    {
        return $this->itemResults()->whereIn('status', ['ok', 'baik'])->count();
    }

    public function getNgCountAttribute()
    {
        return $this->itemResults()->whereNotIn('status', ['ok', 'baik'])->count();
    }

    /**
     * Label jenis pengecekan untuk tampilan
     */
    public function getTypeLabel()
    {
        $labels = [
            'routine' => 'Pengecekan Rutin',
            'body' => 'Pengecekan Body',
        ];
        return $labels[$this->type] ?? $this->type;
    }

    public function getSubTypeLabel()
    {
        $labels = [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
        ];
        return $labels[$this->sub_type] ?? '-';
    }

}
