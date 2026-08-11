<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratSpdBudget extends Model
{
    use HasFactory;

    protected $table = 'surat_spd_budgets';

    protected $fillable = [
        'surat_spd_id',
        'tanggal',
        'nominal',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * Relasi ke Surat SPD
     */
    public function suratSpd()
    {
        return $this->belongsTo(SuratSpd::class, 'surat_spd_id');
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get formatted nominal
     */
    public function getFormattedNominalAttribute(): string
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    /**
     * Get formatted tanggal
     */
    public function getFormattedTanggalAttribute(): string
    {
        return $this->tanggal->translatedFormat('d F Y');
    }

    /**
     * Get short formatted tanggal
     */
    public function getShortTanggalAttribute(): string
    {
        return $this->tanggal->translatedFormat('d M Y');
    }
}
