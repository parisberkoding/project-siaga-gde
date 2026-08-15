<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehiclePartsReplacement extends Model
{
    use HasFactory;

    protected $table = 'vehicle_parts_replacements';

    protected $fillable = [
        'vpr_code',
        'vehicle_checking_detail_id',
        'vehicle_id',
        'nopol',
        'dispatcher_id',
        'last_driver_id',
        'desired_driver_id',
        'approved_by',
        'status',
        'scheduled_date',
        'service_location',
        'rejection_reason',
        'approved_at',
        'rejected_at',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Route model binding: pakai vpr_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'vpr_code';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function checkingDetail()
    {
        return $this->belongsTo(VehicleCheckingDetail::class, 'vehicle_checking_detail_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_id');
    }

    /**
     * Scope unit bisnis: part replacement dari kendaraan yang detailnya berada
     * di site dalam unit. Null = tanpa scope (global / pusat).
     */
    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->whereHas('vehicle', fn ($q) => $q->inUnit($siteIds));
    }

    public function dispatcher()
    {
        return $this->belongsTo(User::class, 'dispatcher_id');
    }

    public function lastDriver()
    {
        return $this->belongsTo(User::class, 'last_driver_id');
    }

    public function desiredDriver()
    {
        return $this->belongsTo(User::class, 'desired_driver_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function replacementDetails()
    {
        return $this->hasMany(VehiclePartsReplacementDetail::class, 'vehicle_parts_replacement_id');
    }

    public function transaction()
    {
        return $this->hasOne(VehiclePartsReplacementTransaction::class, 'vehicle_parts_replacement_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInService($query)
    {
        return $query->where('status', 'in_service');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeActiveRequests($query)
    {
        return $query->whereNotIn('status', ['rejected', 'completed']);
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeForDispatcher($query, $dispatcherId)
    {
        return $query->where('dispatcher_id', $dispatcherId);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Generate vpr_code
     */
    public static function generateCode($nopol)
    {
        $cleanNopol = str_replace(' ', '', $nopol);
        $yearMonth = now()->format('Y-m');
        $lastSeq = self::where('vpr_code', 'like', "VPR-{$cleanNopol}-{$yearMonth}%")->count() + 1;
        $seq = str_pad($lastSeq, 3, '0', STR_PAD_LEFT);
        return "VPR-{$cleanNopol}-{$yearMonth}-{$seq}";
    }

    /**
     * Approve permohonan
     */
    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject permohonan
     */
    public function reject($userId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Set jadwal service
     */
    public function setSchedule($date, $location = null, $desiredDriverId = null)
    {
        $data = [
            'status' => 'scheduled',
            'scheduled_date' => $date,
            'scheduled_at' => now(),
        ];

        if ($location) $data['service_location'] = $location;
        if ($desiredDriverId) $data['desired_driver_id'] = $desiredDriverId;

        $this->update($data);
    }

    /**
     * Tandai sedang di service
     */
    public function markInService()
    {
        $this->update(['status' => 'in_service']);
    }

    /**
     * Tandai selesai
     */
    public function markCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Label status untuk tampilan
     */
    public function getStatusLabel()
    {
        $labels = [
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'scheduled' => 'Terjadwal',
            'in_service' => 'Sedang Di Service',
            'completed' => 'Selesai',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Warna badge status
     */
    public function getStatusBadgeClass()
    {
        return match ($this->status) {
            'pending' => 'bg-warning',
            'approved' => 'bg-info',
            'rejected' => 'bg-danger',
            'scheduled' => 'bg-primary',
            'in_service' => 'bg-secondary',
            'completed' => 'bg-success',
            default => 'bg-secondary',
        };
    }

    /**
     * Jumlah item yang perlu diganti
     */
    public function getItemCountAttribute()
    {
        return $this->replacementDetails()->count();
    }
}
