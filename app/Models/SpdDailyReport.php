<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpdDailyReport extends Model
{
    use HasFactory;

    protected $table = 'spd_daily_reports';

    protected $fillable = [
        'slug',
        'surat_spd_id',
        'user_id',
        'tanggal',
        'uraian',
        'catatan',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'submitted_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';

    /**
     * Boot method untuk auto-generate slug URL-safe.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($daily) {
            if (empty($daily->slug)) {
                $daily->slug = 'LHP-' . strtoupper(bin2hex(random_bytes(5)));
            }
        });
    }

    /**
     * Gunakan slug sebagai route key agar ID tidak tampil di URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function suratSpd()
    {
        return $this->belongsTo(SuratSpd::class, 'surat_spd_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function strukItems()
    {
        return $this->hasMany(StrukItem::class, 'spd_daily_report_id')->orderBy('urutan');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isDraft() ? 'Draft' : 'Terkirim';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->isDraft() ? 'bg-warning text-dark' : 'bg-success';
    }

    /**
     * Total nominal item laporan harian (kategori pengeluaran saja).
     */
    public function getTotalNominalAttribute(): float
    {
        return (float) $this->strukItems
            ->whereIn('struk_kategori', ['BBM', 'Tol', 'Parkir', 'Lainnya'])
            ->sum('nominal');
    }

    /**
     * Jumlah item (termasuk foto tambahan).
     */
    public function getItemCountAttribute(): int
    {
        return $this->strukItems->count();
    }
}
