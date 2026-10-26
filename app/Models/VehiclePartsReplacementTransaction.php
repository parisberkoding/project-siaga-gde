<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class VehiclePartsReplacementTransaction extends Model
{
    use HasFactory;

    protected $table = 'vehicle_parts_replacement_transactions';

    protected $fillable = [
        'vprt_code',
        'vehicle_parts_replacement_id',
        'vpr_code',
        'driver_id',
        'service_location',
        'service_date',
        'total_amount',
        'proof_photo',
        'deadline_date',
        'reported_at',
        'is_overdue',
    ];

    protected $casts = [
        'service_date' => 'date',
        'deadline_date' => 'date',
        'reported_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'is_overdue' => 'boolean',
    ];

    /**
     * Route model binding: pakai vprt_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'vprt_code';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function replacement()
    {
        return $this->belongsTo(VehiclePartsReplacement::class, 'vehicle_parts_replacement_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function details()
    {
        return $this->hasMany(VprtDetail::class, 'vprt_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeOverdue($query)
    {
        return $query->where('is_overdue', true);
    }

    public function scopeReportedToday($query)
    {
        return $query->whereDate('reported_at', Carbon::today());
    }

    /**
     * Scope: service selesai dalam 24 jam terakhir (untuk badge dispatcher)
     */
    public function scopeRecentlyCompleted($query)
    {
        return $query->where('reported_at', '>=', Carbon::now()->subDay());
    }

    public function scopeForDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Generate vprt_code
     */
    public static function generateCode($vprCode)
    {
        // Ambil bagian nopol-tahun-bulan dari VPR code
        $shortCode = str_replace('VPR-', '', $vprCode);
        $lastSeq = self::where('vprt_code', 'like', "VPRT-{$shortCode}%")->count() + 1;
        $seq = str_pad($lastSeq, 3, '0', STR_PAD_LEFT);
        return "VPRT-{$shortCode}-{$seq}";
    }

    /**
     * Auto-set deadline_date saat service_date di-set
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if ($model->service_date && !$model->deadline_date) {
                $model->deadline_date = Carbon::parse($model->service_date)->addDays(3);
            }
            if (!$model->reported_at) {
                $model->reported_at = now();
            }
        });
    }

    /**
     * Cek dan update overdue status (untuk cron job)
     */
    public static function refreshOverdueStatuses()
    {
        self::whereNull('reported_at')
            ->where('deadline_date', '<', Carbon::today())
            ->where('is_overdue', false)
            ->update(['is_overdue' => true]);
    }

    /**
     * Hitung total dari details
     */
    public function calculateTotalFromDetails()
    {
        return $this->details()->sum('total_price');
    }

    /**
     * Cek apakah total_amount cocok dengan sum details
     */
    public function isTotalMatched()
    {
        return abs($this->total_amount - $this->calculateTotalFromDetails()) < 0.01;
    }

    /**
     * Formatted total
     */
    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    /**
     * Sisa hari deadline
     */
    public function getDaysUntilDeadlineAttribute()
    {
        if (!$this->deadline_date) return null;
        return Carbon::today()->diffInDays($this->deadline_date, false);
    }

    /**
     * Badge: baru selesai service (< 24 jam)
     */
    public function isRecentlyCompleted()
    {
        return $this->reported_at && $this->reported_at->diffInHours(now()) < 24;
    }
}
