<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class VehicleCheckingSchedule extends Model
{
    use HasFactory;

    protected $table = 'vehicle_checking_schedules';

    protected $fillable = [
        'schedule_code',
        'vehicle_id',
        'check_frequency',
        'assigned_dispatcher_id',
        'last_checked_at',
        'last_checked_by',
        'next_due_date',
        'status',
        'is_active',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'next_due_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Route model binding: pakai schedule_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'schedule_code';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_id');
    }

    /**
     * Scope unit bisnis: jadwal pengecekan dari kendaraan yang detailnya berada
     * di site dalam unit. Null = tanpa scope (global / pusat).
     */
    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->whereHas('vehicle', fn ($q) => $q->inUnit($siteIds));
    }

    public function assignedDispatcher()
    {
        return $this->belongsTo(User::class, 'assigned_dispatcher_id');
    }

    public function lastChecker()
    {
        return $this->belongsTo(User::class, 'last_checked_by');
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeDueToday($query)
    {
        return $query->where('status', 'due');
    }

    public function scopeDueOrOverdue($query)
    {
        return $query->whereIn('status', ['due', 'overdue']);
    }

    public function scopeForDispatcher($query, $dispatcherId)
    {
        return $query->where('assigned_dispatcher_id', $dispatcherId);
    }

    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('check_frequency', $frequency);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Generate schedule_code
     */
    public static function generateCode($nopol, $frequency)
    {
        $cleanNopol = str_replace(' ', '', $nopol);
        return 'SCH-' . $cleanNopol . '-' . strtoupper($frequency);
    }

    /**
     * Hitung next_due_date berdasarkan frekuensi
     */
    public static function calculateNextDueDate($frequency, Carbon $fromDate = null)
    {
        $from = $fromDate ?: Carbon::now();

        return match ($frequency) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addDays(30),
            'body' => $from->copy()->addDays(30),
            default => $from->copy()->addDay(),
        };
    }

    /**
     * Update setelah pengecekan dilakukan
     */
    public function markAsChecked($dispatcherId)
    {
        $now = Carbon::now();
        $this->update([
            'last_checked_at' => $now,
            'last_checked_by' => $dispatcherId,
            'next_due_date' => self::calculateNextDueDate($this->check_frequency, $now),
            'status' => 'completed',
        ]);
    }

    /**
     * Update status berdasarkan tanggal (untuk cron job)
     */
    public static function refreshStatuses()
    {
        $today = Carbon::today();

        // Overdue: next_due_date < today
        self::active()
            ->where('next_due_date', '<', $today)
            ->where('status', '!=', 'overdue')
            ->update(['status' => 'overdue']);

        // Due: next_due_date = today
        self::active()
            ->where('next_due_date', $today)
            ->where('status', '!=', 'due')
            ->update(['status' => 'due']);

        // Completed yang sudah lewat due date-nya jadi pending lagi
        self::active()
            ->where('status', 'completed')
            ->where('next_due_date', '<=', $today)
            ->update(['status' => 'due']);
    }

    /**
     * Label frekuensi untuk tampilan
     */
    public function getFrequencyLabel()
    {
        $labels = [
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            'body' => 'Body',
        ];
        return $labels[$this->check_frequency] ?? $this->check_frequency;
    }

    /**
     * Warna badge status
     */
    public function getStatusBadgeClass()
    {
        return match ($this->status) {
            'overdue' => 'bg-danger',
            'due' => 'bg-warning',
            'completed' => 'bg-success',
            'pending' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    /**
     * Berapa hari lagi / telat berapa hari
     */
    public function getDaysUntilDueAttribute()
    {
        if (!$this->next_due_date) return null;
        return Carbon::today()->diffInDays($this->next_due_date, false);
    }
}
