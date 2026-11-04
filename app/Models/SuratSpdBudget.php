<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * SuratSpdBudget — dana perjalanan dinas per baris.
 *
 * Dipakai untuk dua keperluan sekaligus:
 *   1. Rencana/budget per tanggal (basis perhitungan over-budget SPD).
 *   2. Lembar Lampiran Pemberian Dana Perjalanan Dinas — baris pencairan
 *      (tanggal, bentuk Tunai/Transfer, nomor referensi perbankan, rekening
 *      tujuan) yang dikelola oleh Divisi GA.
 */
class SuratSpdBudget extends Model
{
    use HasFactory;

    protected $table = 'surat_spd_budgets';

    protected $fillable = [
        'surat_spd_id',
        'tanggal',
        'nominal',
        'bentuk_pencairan',
        'bank_reference',
        'rek_tujuan',
        'catatan',
        'urutan',
        'sumber',
        'surat_spd_extension_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
        'urutan' => 'integer',
    ];

    /**
     * Bentuk pencairan yang valid.
     */
    public const BENTUK_PENCAIRAN = ['Tunai', 'Transfer'];

    /**
     * Sumber baris pemberian dana.
     */
    public const SUMBER_AWAL = 'awal';
    public const SUMBER_TAMBAHAN = 'tambahan';
    public const SUMBER_PERPANJANGAN = 'perpanjangan';

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

    /**
     * Record perpanjangan yang menjadi dasar pemberian dana ini (bila ada).
     */
    public function extension()
    {
        return $this->belongsTo(SuratSpdExtension::class, 'surat_spd_extension_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Urutan tampil pada dokumen: urutan manual lalu tanggal.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan')->orderBy('tanggal');
    }

    /**
     * Hanya baris yang sudah punya data pencairan (bentuk terisi).
     */
    public function scopePencairan($query)
    {
        return $query->whereNotNull('bentuk_pencairan');
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
        return $this->tanggal ? $this->tanggal->translatedFormat('d F Y') : '-';
    }

    /**
     * Get short formatted tanggal
     */
    public function getShortTanggalAttribute(): string
    {
        return $this->tanggal ? $this->tanggal->translatedFormat('d M Y') : '-';
    }

    /**
     * Label bentuk pencairan untuk cetak; default Tunai bila belum diisi
     * (kompatibel dengan data budget lama yang belum punya bentuk pencairan).
     */
    public function getBentukPencairanLabelAttribute(): string
    {
        return $this->bentuk_pencairan ?: 'Tunai';
    }

    /**
     * Benar bila baris ini dicairkan lewat transfer bank.
     */
    public function isTransfer(): bool
    {
        return $this->bentuk_pencairan === 'Transfer';
    }

    /**
     * Baris ini merupakan penambahan dana karena perpanjangan (ETD).
     */
    public function isPerpanjangan(): bool
    {
        return $this->sumber === self::SUMBER_PERPANJANGAN;
    }

    /**
     * Label sumber dana untuk ditampilkan pada tabel pemberian dana.
     */
    public function getSumberLabelAttribute(): string
    {
        return match ($this->sumber) {
            self::SUMBER_PERPANJANGAN => 'Perpanjangan',
            self::SUMBER_TAMBAHAN => 'Tambahan',
            default => 'Awal',
        };
    }
}
