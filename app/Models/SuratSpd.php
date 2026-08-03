<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class SuratSpd extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'surat_spds';

    protected $fillable = [
        'nomor_surat',
        'nomor_urut',
        'pemberi_tugas_nama',
        'pemberi_tugas_jabatan',
        'user_id',
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

            // Calculate lama perjalanan
            $surat->calculateLamaPerjalanan();

            // Calculate total budget
            $surat->calculateTotalBudget();

            // Set tanggal surat jika belum ada
            if (empty($surat->tanggal_surat)) {
                $surat->tanggal_surat = 'Patuha, ' . Carbon::now()->translatedFormat('d F Y');
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
     * Gunakan nomor_surat sebagai route key agar ID tidak tampil di URL.
     */
    public function getRouteKeyName(): string
    {
        return 'nomor_surat';
    }

    /**
     * Generate nomor surat otomatis
     * Format: [nomer].TGS/PTH1.30-GDE/[bulan_romawi]/[tahun]
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

        // Format: 001.TGS/PTH1.30-GDE/XII/2025
        $this->nomor_surat = sprintf(
            '%03d.TGS/PTH1.30-GDE/%s/%s',
            $nomorUrut,
            $bulan,
            $tahun
        );
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
        return $this->lama_perjalanan_hari . ' hari';
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function user()
    {
        return $this->belongsTo(User::class);
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
     * Budget per tanggal untuk surat ini
     */
    public function budgets()
    {
        return $this->hasMany(SuratSpdBudget::class, 'surat_spd_id')->orderBy('tanggal');
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
        if (!$this->is_extension) {
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
     * Check apakah deadline LPJ sudah lewat
     */
    public function isLpjDeadlineOverdue(): bool
    {
        if (!$this->lpj_deadline) {
            return false;
        }

        return Carbon::now()->greaterThan($this->lpj_deadline);
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
        if (!$this->lpj_deadline) {
            return 0;
        }

        $now = Carbon::now();
        $deadline = Carbon::parse($this->lpj_deadline);

        if ($now->greaterThan($deadline)) {
            return -$now->diffInDays($deadline); // Negatif jika sudah lewat
        }

        return $now->diffInDays($deadline);
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
     * @param Carbon|string $tanggalKembaliBaru Tanggal kembali baru
     * @param string|null $alasanPerpanjangan Alasan perpanjangan
     * @param bool $autoApprove Jika true, langsung approved (untuk superadmin)
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
            'catatan' => 'Perpanjangan dari surat: ' . $this->nomor_surat,
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


}
