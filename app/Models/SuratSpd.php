<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuratSpd extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'surat_spds';

    protected $fillable = [
        'slug',
        'nomor_surat',
        'nomor_urut',
        'pemberi_tugas_nama',
        'pemberi_tugas_jabatan',
        'user_id',
        'employee_default_id',
        'pengemudi_nama',
        'tempat_tujuan',
        'melayani_bidang',
        'jumlah_pemakai',
        'vehicle_id',
        'tanggal_berangkat',
        'tanggal_kembali',
        'lama_perjalanan_hari',
        'total_budget',
        'parent_surat_id',
        'is_extension',
        'lpj_deadline',
        'catatan',
        'tanggal_surat',
        'type',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'unit_id',
        'kode_gl',
        'uang_diberikan',
        'jenis_spd',
        'jenis_spd_lainnya',
    ];

    protected $casts = [
        'tanggal_berangkat' => 'date',
        'tanggal_kembali' => 'date',
        'budget_per_hari' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'is_extension' => 'boolean',
        'lpj_deadline' => 'datetime',  // ✅ ADDED
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'uang_diberikan' => 'decimal:2',
    ];

    /**
     * Boot method untuk auto-generate nomor surat dan calculate fields
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($surat) {
            // Generate nomor surat jika belum ada
            if (empty($surat->nomor_surat)) {
                $surat->generateNomorSurat();
            }

            // Slug URL-safe dari nomor surat (slash diganti strip)
            if (empty($surat->slug)) {
                $surat->slug = str_replace('/', '-', $surat->nomor_surat);
            }

            // Calculate lama perjalanan
            $surat->calculateLamaPerjalanan();

            // Calculate total budget
            $surat->calculateTotalBudget();

            // Set tanggal surat jika belum ada (format Indonesia, mengikuti kota unit)
            if (empty($surat->tanggal_surat)) {
                $city = $surat->resolveUnit()?->city ?: 'Patuha';
                $surat->tanggal_surat = $city.', '.Carbon::now()->locale('id')->translatedFormat('d F Y');
            }
        });

        static::updating(function ($surat) {
            // Recalculate jika tanggal berubah
            if ($surat->isDirty(['tanggal_berangkat', 'tanggal_kembali'])) {
                $surat->calculateLamaPerjalanan();
                $surat->calculateTotalBudget();
            }
        });
    }

    /**
     * Gunakan slug (kode surat URL-safe) sebagai route key agar ID tidak
     * tampil di URL. nomor_surat berisi slash sehingga tidak aman untuk URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Resolusi route binding: utamakan slug, fallback ke ID agar URL lama
     * (mis. /surat/{id}) tidak memunculkan 404.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === null) {
            $record = $this->where('slug', $value)->first();
            if ($record) {
                return $record;
            }

            if (ctype_digit((string) $value)) {
                return $this->find((int) $value);
            }

            return null;
        }

        return parent::resolveRouteBinding($value, $field);
    }

    /**
     * Generate nomor surat otomatis
     * Format: [nomer].TGS/[KODE_UNIT]1.30-GDE/[bulan_romawi]/[tahun]
     */
    public function generateNomorSurat()
    {
        $tahun = date('Y');
        $bulan = $this->getRomanMonth(date('n'));

        // Get last nomor urut untuk tahun ini
        $lastSurat = self::whereYear('created_at', $tahun)
            ->orderBy('nomor_urut', 'desc')
            ->first();

        $nomorUrut = $lastSurat ? ($lastSurat->nomor_urut + 1) : 1;
        $this->nomor_urut = $nomorUrut;

        // Kode unit untuk nomor surat (fallback 'PTH' agar format lama tidak berubah).
        $unitCode = $this->resolveUnit()?->code ?: 'PTH';

        // Format: 001.TGS/PTH1.30-GDE/XII/2025
        $this->nomor_surat = sprintf(
            '%03d.TGS/%s1.30-GDE/%s/%s',
            $nomorUrut,
            strtoupper($unitCode),
            $bulan,
            $tahun
        );
    }

    /**
     * Resolve unit surat SPD (dari unit_id, fallback ke unit user pembuat).
     * Dipakai untuk menentukan kode & kota pada nomor/tanggal surat.
     */
    public function resolveUnit(): ?Unit
    {
        if ($this->unit_id) {
            return Unit::find($this->unit_id);
        }

        if ($this->user_id) {
            return User::find($this->user_id)?->unit;
        }

        return null;
    }

    /**
     * Convert month number to Roman numeral
     */
    private function getRomanMonth($month)
    {
        $romans = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

        return $romans[$month - 1];
    }

    /**
     * Calculate lama perjalanan dari selisih tanggal
     */
    public function calculateLamaPerjalanan()
    {
        if ($this->tanggal_berangkat && $this->tanggal_kembali) {
            $berangkat = Carbon::parse($this->tanggal_berangkat);
            $kembali = Carbon::parse($this->tanggal_kembali);
            $this->lama_perjalanan_hari = $berangkat->diffInDays($kembali) + 1;
        }
    }

    /**
     * Calculate total budget
     */
    public function calculateTotalBudget()
    {
        $this->total_budget = $this->lama_perjalanan_hari * $this->budget_per_hari;
    }

    /**
     * Get formatted lama perjalanan
     */
    public function getLamaPerjalananAttribute()
    {
        return $this->lama_perjalanan_hari.' hari';
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Unit bisnis surat SPD (scope data antar unit).
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Master Pekerja yang menjadi pengemudi (opsional — pengemudi bisa
     * pekerja tanpa akun login, sehingga relasi ini melengkapi user()).
     */
    public function employeeDefault()
    {
        return $this->belongsTo(EmployeeDefault::class, 'employee_default_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_id');
    }

    public function details()
    {
        return $this->hasMany(SuratSpdDetail::class, 'surat_spd_id')->orderBy('urutan');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Parent surat (untuk extension)
     * Jika ini adalah surat perpanjangan, ini adalah surat original-nya
     */
    public function parentSurat()
    {
        return $this->belongsTo(SuratSpd::class, 'parent_surat_id');
    }

    /**
     * Child extensions (surat-surat perpanjangan dari surat ini)
     */
    public function childExtensions()
    {
        return $this->hasMany(SuratSpd::class, 'parent_surat_id');
    }

    /**
     * Extension records (log perpanjangan)
     * Untuk surat ORIGINAL: extensions yang menggunakan surat ini sebagai original
     */
    public function extensionRecords()
    {
        return $this->hasMany(SuratSpdExtension::class, 'original_surat_id');
    }

    /**
     * Extension record (log perpanjangan)
     * Untuk surat EXTENSION: record yang mencatat perpanjangan ini
     */
    public function extensionRecord()
    {
        return $this->hasOne(SuratSpdExtension::class, 'extension_surat_id');
    }

    /**
     * LPJ untuk surat ini
     * 1 Surat SPD = 1 LPJ
     */
    public function lpjPenggantian()
    {
        return $this->hasOne(LpjPenggantian::class, 'surat_spd_id');
    }

    /**
     * Laporan harian SPD (pre-fixed report) selama perjalanan dinas
     */
    public function dailyReports()
    {
        return $this->hasMany(SpdDailyReport::class, 'surat_spd_id');
    }

    /**
     * Budget per tanggal untuk surat ini
     */
    public function budgets()
    {
        return $this->hasMany(SuratSpdBudget::class, 'surat_spd_id')->orderBy('tanggal');
    }

    /**
     * Baris pencairan/pemberian dana perjalanan dinas (Lembar Lampiran
     * Pemberian Dana) — sumber data sama dengan budgets(), hanya urutan
     * tampilnya mengikuti kolom `urutan` lalu tanggal.
     */
    public function pencairans()
    {
        return $this->hasMany(SuratSpdBudget::class, 'surat_spd_id')
            ->orderBy('urutan')
            ->orderBy('tanggal');
    }

    /**
     * Total dana yang sudah dicairkan/diberikan untuk surat ini.
     */
    public function getTotalPencairanAttribute(): float
    {
        return (float) $this->pencairans()->sum('nominal');
    }

    /**
     * Rincian struk pengeluaran SPD (upload bukti per item)
     */
    public function strukItems()
    {
        return $this->hasMany(StrukItem::class, 'spd_id')->orderBy('urutan');
    }

    /**
     * Get total budget dari detail budgets
     */
    public function getTotalBudgetFromDetails(): float
    {
        return $this->budgets()->sum('nominal');
    }

    // ============================================================
    // SCOPES
    // ============================================================

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

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOriginalOnly($query)
    {
        return $query->where('is_extension', false);
    }

    public function scopeExtensionOnly($query)
    {
        return $query->where('is_extension', true);
    }

    /**
     * Scope: Surat yang belum ada LPJ-nya
     */
    public function scopeNeedLpj($query)
    {
        return $query->where('status', 'approved')
            ->whereDoesntHave('lpjPenggantian');
    }

    /**
     * Scope: Surat milik user tertentu (termasuk extension)
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope unit bisnis: surat SPD milik unit tertentu.
     * Null = tanpa scope (global / pusat) — melihat semua unit.
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return;
        }

        $query->where('surat_spds.unit_id', $unitId);
    }

    // ============================================================
    // HELPER METHODS - EXTENSION
    // ============================================================

    /**
     * Check apakah surat ini punya perpanjangan yang sudah approved
     */
    public function hasApprovedExtension(): bool
    {
        return $this->childExtensions()
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Get extension terakhir yang approved (jika ada)
     */
    public function getLatestApprovedExtension()
    {
        return $this->childExtensions()
            ->where('status', 'approved')
            ->orderBy('tanggal_kembali', 'desc')
            ->first();
    }

    /**
     * Get semua surat dalam chain (original + semua extension)
     * Berguna untuk menampilkan dropdown di form LPJ
     */
    public function getAllSuratInChain()
    {
        $result = collect();

        // Jika ini extension, cari original dulu
        $original = $this->is_extension ? $this->getOriginalSurat() : $this;

        // Add original
        $result->push($original);

        // Add semua extension yang approved
        $extensions = SuratSpd::where('parent_surat_id', $original->id)
            ->where('status', 'approved')
            ->orderBy('tanggal_berangkat')
            ->get();

        foreach ($extensions as $ext) {
            $result->push($ext);
        }

        return $result;
    }

    /**
     * Get surat original (jika ini extension)
     */
    public function getOriginalSurat()
    {
        if (! $this->is_extension) {
            return $this;
        }

        return $this->parentSurat;
    }

    /**
     * Get jumlah extension untuk surat ini
     */
    public function getExtensionCount(): int
    {
        if ($this->is_extension) {
            // Jika ini extension, hitung dari original
            return $this->parentSurat->childExtensions()->count();
        }

        return $this->childExtensions()->count();
    }

    // ============================================================
    // HELPER METHODS - LPJ
    // ============================================================

    /**
     * Batas waktu submit LPJ yang berlaku.
     *
     * Prioritas `lpj_deadline` (diisi saat surat disetujui / dibuat lewat
     * shortcut). Bila belum terisi (data lama), dipakai aturan default
     * **tanggal_kembali + 3 hari** supaya label overdue tidak pernah "bohong"
     * hanya karena kolomnya kosong.
     *
     * Batas bersifat INKLUSIF: submit pada hari deadline masih dihitung tepat
     * waktu (dibandingkan dengan akhir hari).
     */
    public function getLpjDeadlineEfektifAttribute(): ?Carbon
    {
        if ($this->lpj_deadline) {
            return Carbon::parse($this->lpj_deadline)->endOfDay();
        }

        if ($this->tanggal_kembali) {
            return Carbon::parse($this->tanggal_kembali)->addDays(3)->endOfDay();
        }

        return null;
    }

    /**
     * Check apakah deadline LPJ sudah lewat.
     *
     * CATATAN: ini hanya PENANDA (label overdue). Tidak ada satu pun alur yang
     * menutup form LPJ karena lewat deadline — driver tetap boleh membuat &
     * submit LPJ, hanya tercatat terlambat.
     */
    public function isLpjDeadlineOverdue(): bool
    {
        $deadline = $this->lpj_deadline_efektif;

        return $deadline !== null && Carbon::now()->greaterThan($deadline);
    }

    /**
     * Berapa hari sudah melewati batas submit LPJ (0 bila belum lewat).
     */
    public function getLpjOverdueDaysAttribute(): int
    {
        $deadline = $this->lpj_deadline_efektif;

        if (! $deadline || ! Carbon::now()->greaterThan($deadline)) {
            return 0;
        }

        return (int) $deadline->startOfDay()->diffInDays(Carbon::now()->startOfDay());
    }

    /**
     * Surat sudah disetujui tetapi driver belum submit LPJ.
     */
    public function getMenungguLpjAttribute(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        // Relasi sudah di-eager-load di daftar; hindari query berulang.
        if ($this->relationLoaded('lpjPenggantian')) {
            return $this->lpjPenggantian === null;
        }

        return ! $this->hasLpj();
    }

    /**
     * Surat menunggu LPJ DAN sudah melewati batas 3 hari.
     */
    public function getLpjTerlambatAttribute(): bool
    {
        return $this->menunggu_lpj && $this->isLpjDeadlineOverdue();
    }

    /**
     * Check apakah surat ini sudah ada LPJ-nya
     */
    public function hasLpj(): bool
    {
        return $this->lpjPenggantian()->exists();
    }

    /**
     * Get sisa hari sebelum deadline LPJ
     */
    public function getDaysUntilLpjDeadline(): int
    {
        $deadline = $this->lpj_deadline_efektif;

        if (! $deadline) {
            return 0;
        }

        // Carbon 3: diffInDays() bertanda → positif bila deadline masih di depan,
        // negatif bila sudah lewat. (Sebelumnya tanda untuk kasus lewat dibalik.)
        return (int) Carbon::now()->startOfDay()->diffInDays($deadline->copy()->startOfDay());
    }

    /**
     * Calculate dan set LPJ deadline
     * Deadline = tanggal_kembali + 3 hari
     */
    public function calculateLpjDeadline()
    {
        if ($this->tanggal_kembali) {
            $this->lpj_deadline = Carbon::parse($this->tanggal_kembali)
                ->addDays(3)
                ->endOfDay(); // Set ke akhir hari (23:59:59)
        }
    }

    // ============================================================
    // STATUS METHODS
    // ============================================================

    /**
     * Approve surat
     * Set status, approver, dan calculate LPJ deadline
     */
    public function approve($userId)
    {
        // Calculate LPJ deadline sebelum update
        $this->calculateLpjDeadline();

        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'lpj_deadline' => $this->lpj_deadline,
        ]);
    }

    /**
     * Reject surat
     */
    public function reject($userId, $reason)
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark as completed (setelah LPJ disubmit dan approved)
     */
    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }

    // ============================================================
    // EXTENSION CREATION
    // ============================================================

    /**
     * Create extension (perpanjangan)
     *
     * @param  Carbon|string  $tanggalKembaliBaru  Tanggal kembali baru
     * @param  string|null  $alasanPerpanjangan  Alasan perpanjangan
     * @param  bool  $autoApprove  Jika true, langsung approved (untuk superadmin)
     * @return SuratSpd Surat extension yang baru dibuat
     */
    public function createExtension($tanggalKembaliBaru, $alasanPerpanjangan = null, $autoApprove = false)
    {
        $tanggalKembaliBaru = Carbon::parse($tanggalKembaliBaru);
        $durasiTambahan = Carbon::parse($this->tanggal_kembali)->diffInDays($tanggalKembaliBaru);

        // Create surat extension baru
        $extensionSurat = self::create([
            'pemberi_tugas_nama' => $this->pemberi_tugas_nama,
            'pemberi_tugas_jabatan' => $this->pemberi_tugas_jabatan,
            'user_id' => $this->user_id,
            'pengemudi_nama' => $this->pengemudi_nama,
            'tempat_tujuan' => $this->tempat_tujuan,
            'melayani_bidang' => $this->melayani_bidang,
            'jumlah_pemakai' => $this->jumlah_pemakai,
            'vehicle_id' => $this->vehicle_id,
            // Tanggal berangkat = tanggal kembali surat sebelumnya
            'tanggal_berangkat' => $this->tanggal_kembali,
            'tanggal_kembali' => $tanggalKembaliBaru,
            'budget_per_hari' => $this->budget_per_hari,
            'parent_surat_id' => $this->is_extension ? $this->parent_surat_id : $this->id,
            'is_extension' => true,
            'catatan' => 'Perpanjangan dari surat: '.$this->nomor_surat,
            'status' => $autoApprove ? 'approved' : 'pending',
        ]);

        // Copy details (nama penumpang)
        foreach ($this->details as $detail) {
            $extensionSurat->details()->create([
                'nama_lengkap' => $detail->nama_lengkap,
                'urutan' => $detail->urutan,
            ]);
        }

        // Set LPJ deadline jika auto approve
        if ($autoApprove) {
            $extensionSurat->calculateLpjDeadline();
            $extensionSurat->save();
        }

        // Create extension record (log)
        SuratSpdExtension::create([
            'original_surat_id' => $this->is_extension ? $this->parent_surat_id : $this->id,
            'extension_surat_id' => $extensionSurat->id,
            'nomor_surat_extension' => $extensionSurat->nomor_surat,
            'tanggal_kembali_original' => $this->tanggal_kembali,
            'tanggal_kembali_baru' => $tanggalKembaliBaru,
            'durasi_tambahan_hari' => $durasiTambahan,
            'budget_tambahan' => $durasiTambahan * $this->budget_per_hari,
            'alasan_perpanjangan' => $alasanPerpanjangan,
            'status' => $autoApprove ? 'approved' : 'pending',
            'approved_by' => $autoApprove ? auth()->id() : null,
            'approved_at' => $autoApprove ? now() : null,
        ]);

        return $extensionSurat;
    }

    // barcode signature
    public function barcodeSignature()
    {
        return $this->hasOne(SpdBarcodeSignature::class, 'spd_id');
    }

    /**
     * Riwayat penambahan dana pada surat SPD (setelah disetujui).
     */
    public function fundAdditions()
    {
        return $this->hasMany(SuratSpdFundAddition::class, 'surat_spd_id')->orderBy('created_at', 'desc');
    }

    /**
     * Total uang yang diberikan ke driver = uang awal + seluruh penambahan dana.
     */
    public function getTotalUangDiberikanAttribute(): float
    {
        $awal = (float) ($this->uang_diberikan ?? 0);
        $tambahan = (float) $this->fundAdditions()->sum('nominal_tambahan');

        return $awal + $tambahan;
    }
}
