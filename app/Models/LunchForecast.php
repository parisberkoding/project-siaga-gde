<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris forecast makan siang per karyawan per tanggal.
 *
 * Pengganti LunchTracking lama: tidak ada lagi relasi ke trip/vehicle.
 * predicted_site_id diisi Forecast Engine (H-1), actual_site_id diisi
 * Learning Engine dari lunch_transactions (QR Attendance).
 *
 * prediction_source: manual | confirmation | historical | default
 * ('legacy_vehicle' hanya ada di baris historis era vehicle tracking).
 */
class LunchForecast extends Model
{
    use HasFactory;

    protected $table = 'lunch_forecasts';

    protected $fillable = [
        'user_id',
        'employee_default_id',
        'lunch_date',
        'predicted_site_id',
        'actual_site_id',
        'prediction_source',
        'confidence',
        'is_prediction_correct',
        'forecast_batch_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lunch_date'            => 'date',
        'confidence'            => 'decimal:2',
        'is_prediction_correct' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(EmployeeDefault::class, 'employee_default_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function predictedSite()
    {
        return $this->belongsTo(Site::class, 'predicted_site_id');
    }

    public function actualSite()
    {
        return $this->belongsTo(Site::class, 'actual_site_id');
    }

    public function forecastBatch()
    {
        return $this->belongsTo(LunchForecastBatch::class, 'forecast_batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('prediction_source', $source);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('lunch_date', $date);
    }

    /**
     * Site hasil final: actual (dari transaksi QR) jika sudah ada,
     * jika belum pakai predicted (forecast H-1 yang berlaku).
     */
    public function getFinalSiteAttribute(): ?Site
    {
        return $this->actualSite ?? $this->predictedSite;
    }

    public function sourceLabel(): string
    {
        return match ($this->prediction_source) {
            'manual'       => 'Manual Override',
            'self_booking' => 'Booking Mandiri',
            'confirmation' => 'Konfirmasi Besok',
            'historical'   => 'Pola Historis',
            'default'      => 'Default Site',
            'legacy_vehicle' => 'Legacy (Vehicle)',
            default        => $this->prediction_source,
        };
    }

    public function sourceBadgeClass(): string
    {
        return match ($this->prediction_source) {
            'manual'       => 'primary',
            'self_booking' => 'dark',
            'confirmation' => 'success',
            'historical'   => 'info',
            'default'      => 'warning',
            default        => 'secondary',
        };
    }
}
