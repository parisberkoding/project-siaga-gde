<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasSafeSlugRouteKey;
use Illuminate\Database\Eloquent\Model;

class StrukItem extends Model
{
    use HasFactory, HasSafeSlugRouteKey;

    protected $table = 'struk_items';

    protected $fillable = [
        'slug',
        'spd_id',
        'spd_daily_report_id',
        'nomor_surat_spd',
        'lpj_id',
        'lpj_detail_id',
        'item_code',
        'path_image',
        'struk_kategori',
        'kategori_source',
        'metadata_json',
        'nominal',
        'tanggal',
        'keterangan',
        'ocr_text',
        'urutan',
        'edited_at',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'tanggal' => 'date',
        'metadata_json' => 'array',
        'edited_at' => 'datetime',
    ];

    /**
     * Boot method untuk auto-generate slug URL-safe.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->slug)) {
                $item->slug = 'ITM-' . strtoupper(bin2hex(random_bytes(5)));
            }
        });
    }

    /**
     * Gunakan slug sebagai route key agar ID tidak tampil di URL.
     * item_code berisi slash (nomor surat) sehingga tidak aman untuk URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Kategori yang diizinkan (self-label) — di luar itu = Lainnya.
     *
     * Sejak dokumen LPJ SPD baru, "Hotel + Makan" dipecah menjadi dua kategori
     * mandiri ("Makan" & "Hotel") agar bisa dipetakan ke kolom biaya yang
     * berbeda pada Lembar Persetujuan & Lembar Ringkasan Penggunaan.
     */
    public const KATEGORI = ['BBM', 'Tol', 'Parkir', 'Makan', 'Hotel', 'Lainnya', 'Tambahan'];

    /**
     * Kategori yang dihitung sebagai pengeluaran (Tambahan = dokumentasi, bukan biaya).
     */
    public const KATEGORI_BIAYA = ['BBM', 'Tol', 'Parkir', 'Makan', 'Hotel', 'Lainnya'];

    /**
     * Label kolom biaya pada dokumen cetak LPJ.
     */
    public const KATEGORI_LABEL = [
        'Tol'     => 'Biaya Tol',
        'Parkir'  => 'Biaya Parkir',
        'BBM'     => 'Biaya BBM',
        'Makan'   => 'Biaya Makan',
        'Hotel'   => 'Biaya Hotel',
        'Lainnya' => 'Lain-lain',
        'Tambahan' => 'Dokumentasi Tambahan',
    ];

    /**
     * Prefix kode per kategori: S.BBM, S.TOL, S.PRK, S.MKN, S.HTL, S.LAINNYA, S.TBH
     */
    public const KATEGORI_PREFIX = [
        'BBM'      => 'S.BBM',
        'Tol'      => 'S.TOL',
        'Parkir'   => 'S.PRK',
        'Makan'    => 'S.MKN',
        'Hotel'    => 'S.HTL',
        'Lainnya'  => 'S.LAINNYA',
        'Tambahan' => 'S.TBH',
    ];

    /**
     * Daftar kategori untuk aturan validasi Laravel (`in:...`).
     *
     * Nilai lama "Hotel + Makan" tetap diterima agar payload/klien lama tidak
     * langsung ditolak; normalisasi ke 'Makan'/'Hotel' terjadi di
     * normalizeKategori().
     */
    public static function validationList(): string
    {
        return implode(',', array_merge(self::KATEGORI, ['Hotel + Makan']));
    }

    /**
     * Daftar kategori BIAYA saja (tanpa Tambahan) untuk aturan validasi form
     * LPJ, karena baris `lpj_penggantian_details` hanya menampung pengeluaran.
     */
    public static function validationListBiaya(): string
    {
        return implode(',', array_merge(self::KATEGORI_BIAYA, ['Hotel + Makan']));
    }

    /**
     * Generate item_code unik.
     * Format: S.{KATEGORI}-[Nomer Surat SPD]-DD-MM-YYYY-[20 digit unik]
     * Contoh: S.BBM-001.TGS/PTH1.30-GDE/VIII/2026-05-08-38492017462910382745
     */
    public static function generateItemCode(string $kategori, string $nomorSuratSpd, $tanggal = null): string
    {
        $prefix = self::KATEGORI_PREFIX[$kategori] ?? 'S.LAINNYA';
        $tanggalStr = $tanggal ? \Carbon\Carbon::parse($tanggal)->format('d-m-Y') : now()->format('d-m-Y');
        $unique = bin2hex(random_bytes(10));

        return "{$prefix}-{$nomorSuratSpd}-{$tanggalStr}-{$unique}";
    }

    /**
     * Normalisasi kategori (hasil AI / input bebas) ke salah satu kategori valid.
     * Teks bebas di luar BBM/Tol/Parkir/Makan/Hotel dikembalikan sebagai 'Lainnya'.
     *
     * Catatan kompatibilitas: nilai lama "Hotel + Makan" tetap diterima dan
     * dipetakan ulang ke 'Makan' (kategori yang paling umum) agar data lama /
     * payload yang belum diperbarui tidak langsung menjadi 'Lainnya'.
     */
    public static function normalizeKategori(?string $kategori): string
    {
        $kategori = trim((string) $kategori);

        if (in_array($kategori, self::KATEGORI, true)) {
            return $kategori;
        }

        if ($kategori === 'Hotel + Makan') {
            return 'Makan';
        }

        $lower = strtolower($kategori);
        if (str_contains($lower, 'bbm') || str_contains($lower, 'bahan bakar') || str_contains($lower, 'fuel') || str_contains($lower, 'premium') || str_contains($lower, 'pertamax') || str_contains($lower, 'solar') || str_contains($lower, 'dex')) {
            return 'BBM';
        }
        if (str_contains($lower, 'tol') || str_contains($lower, 'toll')) {
            return 'Tol';
        }
        if (str_contains($lower, 'parkir') || str_contains($lower, 'parking')) {
            return 'Parkir';
        }
        if (str_contains($lower, 'hotel') || str_contains($lower, 'penginapan') || str_contains($lower, 'inn') || str_contains($lower, 'room') || str_contains($lower, 'kamar') || str_contains($lower, 'resort') || str_contains($lower, 'losmen') || str_contains($lower, 'homestay')) {
            return 'Hotel';
        }
        if (str_contains($lower, 'makan') || str_contains($lower, 'restoran') || str_contains($lower, 'restaurant') || str_contains($lower, 'konsumsi') || str_contains($lower, 'food') || str_contains($lower, 'catering') || str_contains($lower, 'kafe') || str_contains($lower, 'cafe') || str_contains($lower, 'minum')) {
            return 'Makan';
        }
        if (str_contains($lower, 'tambahan') || str_contains($lower, 'odometer') || str_contains($lower, 'odoo') || str_contains($lower, 'dashboard') || str_contains($lower, 'km') || str_contains($lower, 'speedometer')) {
            return 'Tambahan';
        }

        return 'Lainnya';
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function suratSpd()
    {
        return $this->belongsTo(SuratSpd::class, 'spd_id');
    }

    public function dailyReport()
    {
        return $this->belongsTo(SpdDailyReport::class, 'spd_daily_report_id');
    }

    public function lpj()
    {
        return $this->belongsTo(LpjPenggantian::class, 'lpj_id');
    }

    public function lpjDetail()
    {
        return $this->belongsTo(LpjPenggantianDetail::class, 'lpj_detail_id');
    }

    // ============================================================
    // HELPERS
    // ============================================================

    public function getFormattedNominalAttribute(): string
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    public function getKategoriBadgeClass(): string
    {
        return match ($this->struk_kategori) {
            'BBM'      => 'bg-danger',
            'Tol'      => 'bg-primary',
            'Parkir'   => 'bg-info',
            'Makan'    => 'bg-success',
            'Hotel'    => 'bg-dark',
            'Tambahan' => 'bg-warning text-dark',
            default    => 'bg-secondary',
        };
    }

    /**
     * Label kategori untuk dokumen cetak ("Biaya Makan", dst).
     */
    public function getKategoriLabelAttribute(): string
    {
        return self::KATEGORI_LABEL[$this->struk_kategori] ?? $this->struk_kategori;
    }

    public function getPathUrlAttribute(): ?string
    {
        if (! $this->path_image) {
            return null;
        }

        // Struk bisa berisi data sensitif → layani lewat rute terproteksi auth
        // (StorageFileController, '/files/storage/{path}') alih-alih symlink publik.
        return url('files/storage/' . $this->path_image);
    }
}
