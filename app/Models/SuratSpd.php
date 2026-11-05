<?php

namespace App\Models;

use App\Models\Concerns\HasSafeSlugRouteKey;
use App\Services\UnitScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;

class SuratSpd extends Model
{
    use HasFactory, HasSafeSlugRouteKey, SoftDeletes;

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
        'extension_count',
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
        'nomor_urut' => 'integer',
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

        // Cascade cleanup: hapus surat (soft delete maupun force delete) juga
        // membersihkan baris turunannya agar tidak ada data yatim. FK cascade
        // di database TIDAK membantu untuk soft delete.
        static::deleting(function (SuratSpd $surat) {
            $surat->cascadeDeleteRelations();
        });

        static::forceDeleting(function (SuratSpd $surat) {
            $surat->cascadeDeleteRelations();
        });
    }

    /**
     * Flag agar cascade tidak berjalan dua kali (forceDelete memicu
     * `forceDeleting` lalu `deleting`).
     */
    private bool $cascadeDeleting = false;

    /**
     * Hapus seluruh relasi turunan surat SPD yang ada.
     *
     * Relasi yang sama-sama memakai tabel surat_spd_budgets (budgets &
     * pencairans) hanya dihapus sekali. LPJ sengaja TIDAK dihapus di sini —
     * LPJ punya alur audit sendiri dan penghapusannya bukan bagian dari
     * pembersihan orphan SPD.
     */
    public function cascadeDeleteRelations(): void
    {
        if ($this->cascadeDeleting) {
            return;
        }

        $this->cascadeDeleting = true;

        $relations = [
            'details',
            'budgets',          // sekaligus pencairans (tabel sama)
            'barcodeSignature',
            'extensionRecords', // log perpanjangan yang memakai surat ini sebagai original
            'extensionRecord',  // log perpanjangan yang memakai surat ini sebagai extension
            'dailyReports',
            'fundAdditions',
        ];

        foreach ($relations as $relation) {
            if (method_exists($this, $relation)) {
                $this->{$relation}()->delete();
            }
        }
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
     *
     * Nomor urut dihitung NUMERIK (bukan orderBy string) dan berlaku global
     * per tahun (tidak di-reset per unit). Baris yang sudah soft-deleted tetap
     * dihitung supaya nomor tidak dipakai ulang (unique index tetap berlaku).
     */
    public function generateNomorSurat()
    {
        $tahun = (int) date('Y');
        $bulan = $this->getRomanMonth((int) date('n'));

        $maxNomorUrut = self::withTrashed()
            ->whereYear('created_at', $tahun)
            ->pluck('nomor_urut')
            ->map(fn ($value) => is_numeric($value) ? (int) $value : 0)
            ->max() ?? 0;

        $nomorUrut = $maxNomorUrut + 1;

        // Kode unit untuk nomor surat (fallback 'PTH' agar format lama tidak berubah).
        $unitCode = strtoupper($this->resolveUnit()?->code ?: 'PTH');

        // Lewati nomor yang sudah terpakai (mis. data lama dengan nomor_urut
        // NULL tetapi nomor_surat-nya sudah ada).
        $batas = $maxNomorUrut + 100;
        do {
            $nomorSurat = sprintf(
                '%03d.TGS/%s1.30-GDE/%s/%s',
                $nomorUrut,
                $unitCode,
                $bulan,
                $tahun
            );

            $sudahDipakai = self::withTrashed()
                ->where('nomor_surat', $nomorSurat)
                ->exists();

            if (! $sudahDipakai) {
                break;
            }

            $nomorUrut++;
        } while ($nomorUrut <= $batas);

        $this->nomor_urut = $nomorUrut;
        $this->nomor_surat = $nomorSurat;
    }

    /**
     * Buat SuratSpd dengan nomor urut yang aman terhadap race condition.
     *
     * Pembuatan dibungkus Cache lock global, dan bila tetap terjadi unique
     * violation (mis. dua proses berbeda) nomor digenerate ulang lalu dicoba
     * lagi maksimal 3 kali sebelum exception aslinya dilempar kembali.
     */
    public static function createWithNumberedSurat(array $attributes): self
    {
        return Cache::lock('spd-nomor-urut', 10)->block(10, function () use ($attributes) {
            // Nomor selalu digenerate oleh model agar tidak pernah memakai
            // nomor urut dari pemanggil (sumber duplikat).
            unset($attributes['nomor_surat'], $attributes['nomor_urut']);

            $lastException = null;

            for ($attempt = 1; $attempt <= 3; $attempt++) {
                try {
                    return self::create($attributes);
                } catch (UniqueConstraintViolationException $e) {
                    $lastException = $e;

                    if ($attempt < 3) {
                        // Beri kesempatan transaksi pesaing commit sebelum
                        // nomor dihitung ulang pada percobaan berikutnya.
                        usleep(50_000 * $attempt);
                    }
                }
            }

            throw $lastException;
        });
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
     * Hitung total budget dari budget per hari (kolom legacy).
     *
     * Saat ini budget utama disimpan pada baris `surat_spd_budgets` dan
     * `total_budget` disinkronkan dari sana. Jangan reset `total_budget`
     * ketika `budget_per_hari` kosong (mis. saat tanggal perpanjangan
     * di-update), karena akan menghapus dana yang sudah tercatat.
     */
    public function calculateTotalBudget()
    {
        $perHari = (float) ($this->attributes['budget_per_hari'] ?? 0);

        if ($perHari > 0) {
            $this->total_budget = $this->lama_perjalanan_hari * $perHari;
        }
    }

    /**
     * Budget per hari efektif.
     *
     * Kolom `budget_per_hari` sudah tidak ada di tabel surat_spds (budget
     * disimpan di surat_spd_budgets / total_budget). Untuk perpanjangan,
     * nilai per hari diturunkan dari total_budget dibagi lama perjalanan,
     * atau dari total baris pemberian dana bila total_budget kosong.
     */
    public function getBudgetPerHariEfektifAttribute(): float
    {
        $langsung = (float) ($this->attributes['budget_per_hari'] ?? 0);

        if ($langsung > 0) {
            return $langsung;
        }

        $lama = (int) $this->lama_perjalanan_hari;

        if ($lama <= 0) {
            return 0.0;
        }

        $total = (float) ($this->total_budget ?? 0);

        if ($total <= 0) {
            $total = (float) $this->budgets()->sum('nominal');
        }

        return $total > 0 ? $total / $lama : 0.0;
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
     * Scope: Surat milik user tertentu (termasuk extension).
     *
     * Surat hasil "Shortcut" bisa dibuat untuk pengemudi yang diambil dari
     * Master Pekerja. Bila akun login pengemudi terhubung lewat
     * `employee_defaults.user_id` (dan kolom `user_id` surat belum terisi),
     * surat tetap harus muncul di POV driver agar ia bisa membuat LPJ.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('surat_spds.user_id', $userId)
                ->orWhereHas('employeeDefault', fn ($employee) => $employee->where('user_id', $userId));
        });
    }

    /**
     * Scope: surat SPD yang SEDANG berjalan pada tanggal tertentu.
     *
     * Dipakai untuk mengunci kendaraan: satu kendaraan yang sedang dipakai
     * perjalanan dinas tidak boleh dipakai untuk permohonan BBM operasional.
     * Status `approved` + rentang tanggal berangkat/kembali (inklusif).
     */
    public function scopeAktifPada($query, $tanggal = null)
    {
        $tanggal = $tanggal ? Carbon::parse($tanggal)->toDateString() : now()->toDateString();

        return $query->where('surat_spds.status', 'approved')
            ->whereDate('surat_spds.tanggal_berangkat', '<=', $tanggal)
            ->whereDate('surat_spds.tanggal_kembali', '>=', $tanggal);
    }

    /**
     * ID kendaraan yang sedang dipakai SPD aktif (unique).
     *
     * @return array<int, int>
     */
    public static function vehicleIdsAktifPada($tanggal = null): array
    {
        return static::query()
            ->aktifPada($tanggal)
            ->whereNotNull('vehicle_id')
            ->pluck('vehicle_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
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

    /**
     * Apakah user boleh melihat/mencetak surat ini (BUG-SEC-001).
     *
     * Aturan:
     *   - user global (Pusat/superadmin/developers) -> semua surat;
     *   - pemilik surat (requester) atau penumpang -> boleh;
     *   - user dengan scope unit -> hanya surat milik unitnya.
     *
     * `unit_id` NULL pada surat hanya boleh diakses user global.
     */
    public function isVisibleToUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $scope = UnitScope::for($user);

        if ($scope->isGlobal()) {
            return true;
        }

        if ($this->user_id !== null && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        // Pengemudi yang ditunjuk lewat Master Pekerja (shortcut) tetapi kolom
        // user_id surat belum terisi tetap boleh melihat suratnya sendiri.
        if ($this->employee_default_id !== null
            && $this->employeeDefault()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($this->details()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Satu unit saja TIDAK cukup: requester lain di unit yang sama tetap
        // tidak boleh membuka dokumen orang lain (BUG-SEC-001). Akses
        // unit-wide hanya untuk peran pengelola (Admin GA, Manager, GA
        // pencairan) — bukan requester/pegawai/dispatcher.
        $peranPengelola = [
            'spd.manage', 'spd.approve', 'spd.assign', 'spd.update',
            'lpj.validate_management', 'lpj.pencairan', 'lpj.export', 'lpj.delete',
        ];

        if (! $user->hasPermission($peranPengelola)) {
            return false;
        }

        return $scope->allowsUnit($this->unit_id);
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
     * Get semua surat dalam chain (original + semua extension, rekursif).
     * Berguna untuk menampilkan dropdown di form LPJ.
     *
     * `parent_surat_id` menunjuk ke surat SEBELUMNYA (bukan selalu root),
     * jadi chain ditelusuri melewati seluruh turunan.
     */
    public function getAllSuratInChain()
    {
        $root = $this->getRootSurat();

        $result = collect([$root]);

        $queue = [$root->id];
        $visited = [$root->id => true];

        while ($queue !== []) {
            $children = SuratSpd::whereIn('parent_surat_id', $queue)
                ->where('status', 'approved')
                ->orderBy('tanggal_berangkat')
                ->get();

            $queue = [];

            foreach ($children as $child) {
                if (isset($visited[$child->id])) {
                    continue;
                }

                $visited[$child->id] = true;
                $result->push($child);
                $queue[] = $child->id;
            }
        }

        return $result;
    }

    /**
     * Get surat original (root dari chain perpanjangan).
     */
    public function getOriginalSurat()
    {
        return $this->getRootSurat();
    }

    /**
     * Telusuri `parentSurat` sampai root. `parent_surat_id` sendiri menunjuk
     * ke surat sebelumnya (immediate previous), sehingga root harus dicari
     * dengan berjalan mundur.
     */
    public function getRootSurat(): self
    {
        $surat = $this;
        $dikunjungi = [];

        while ($surat->parent_surat_id && ! isset($dikunjungi[$surat->id])) {
            $dikunjungi[$surat->id] = true;

            $parent = $surat->parentSurat()->first();

            if (! $parent) {
                break;
            }

            $surat = $parent;
        }

        return $surat;
    }

    /**
     * Get jumlah extension untuk surat ini (seluruh chain).
     */
    public function getExtensionCount(): int
    {
        return $this->getAllSuratInChain()->where('is_extension', true)->count();
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

        // Unit ikut surat sebelumnya (fallback ke unit user pembuat) supaya
        // perpanjangan muncul di queue unit yang benar (scopeInUnit).
        $unitId = $this->unit_id ?: $this->resolveUnit()?->id;

        // `parent_surat_id` menunjuk ke surat IMMEDIATE SEBELUMNYA, bukan root,
        // sehingga perpanjangan #2 terhubung ke perpanjangan #1.
        $extensionSurat = self::createWithNumberedSurat([
            'pemberi_tugas_nama' => $this->pemberi_tugas_nama,
            'pemberi_tugas_jabatan' => $this->pemberi_tugas_jabatan,
            'user_id' => $this->user_id,
            'employee_default_id' => $this->employee_default_id,
            'pengemudi_nama' => $this->pengemudi_nama,
            'tempat_tujuan' => $this->tempat_tujuan,
            'melayani_bidang' => $this->melayani_bidang,
            'jumlah_pemakai' => $this->jumlah_pemakai,
            'vehicle_id' => $this->vehicle_id,
            'unit_id' => $unitId,
            'jenis_spd' => $this->jenis_spd,
            'jenis_spd_lainnya' => $this->jenis_spd_lainnya,
            'kode_gl' => $this->kode_gl,
            'type' => $this->type,
            // Tanggal berangkat = tanggal kembali surat sebelumnya
            'tanggal_berangkat' => $this->tanggal_kembali,
            'tanggal_kembali' => $tanggalKembaliBaru,
            'parent_surat_id' => $this->id,
            'is_extension' => true,
            'catatan' => 'Perpanjangan dari surat: '.$this->nomor_surat,
            'status' => 'pending',
        ]);

        // Perpanjangan TIDAK membawa nominal dari driver. Dana tambahan hanya
        // dicatat Admin GA lewat Pemberian Dana Perpanjangan setelah surat
        // disetujui, sehingga total awalnya nol.
        $extensionSurat->calculateLamaPerjalanan();
        $extensionSurat->total_budget = 0;
        $extensionSurat->save();

        // Copy details (nama penumpang + akun user bila ada)
        foreach ($this->details as $detail) {
            $extensionSurat->details()->create([
                'nama_lengkap' => $detail->nama_lengkap,
                'user_id' => $detail->user_id,
                'urutan' => $detail->urutan,
            ]);
        }

        // Create extension record (log). `original_surat_id` juga menunjuk ke
        // surat sebelumnya agar chain konsisten; root bisa dicapai lewat
        // walk `parentSurat`.
        $extensionRecord = SuratSpdExtension::create([
            'original_surat_id' => $this->id,
            'extension_surat_id' => $extensionSurat->id,
            'nomor_surat_extension' => $extensionSurat->nomor_surat,
            'tanggal_kembali_original' => $this->tanggal_kembali,
            'tanggal_kembali_baru' => $tanggalKembaliBaru,
            'durasi_tambahan_hari' => $durasiTambahan,
            // Nominal ditentukan Admin GA, bukan dihitung/diminta driver.
            'budget_tambahan' => 0,
            'alasan_perpanjangan' => $alasanPerpanjangan,
            'status' => 'pending',
        ]);

        // autoApprove tetap melewati approve() agar propagation chain
        // (tanggal kembali + LPJ deadline) ikut jalan.
        if ($autoApprove) {
            $extensionRecord->approve(auth()->id());
            $extensionSurat->refresh();
        }

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
