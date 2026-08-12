<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LunchDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_date',
        'site_id',
        'employee_count',
        'guest_count',
        'total_count',
        'forecast_batch_id',
        'is_locked',
        'locked_at',
        'locked_by',
    ];

    protected $casts = [
        'distribution_date' => 'date',
        'is_locked'         => 'boolean',
        'locked_at'         => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function forecastBatch()
    {
        return $this->belongsTo(LunchForecastBatch::class, 'forecast_batch_id');
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('distribution_date', $date);
    }

    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope unit bisnis: distribusi makan siang di salah satu site dalam unit.
     * Null = tanpa scope (global / pusat).
     */
    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->whereIn('site_id', $siteIds);
    }
}
