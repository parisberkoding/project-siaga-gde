<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasSafeSlugRouteKey;
use App\Services\UnitScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;  // ✅ ADDED: Missing import

class LpjPenggantian extends Model
{
    use HasFactory, HasSafeSlugRouteKey, SoftDeletes;

    protected $table = 'lpj_penggantians';

    protected $fillable = [
        'slug',
        'lpj_number',
        'surat_spd_id',
        'user_id',
        'nama',
        'jabatan',
        'vehicle_id',
        'nomor_polisi',
        'odo_before',
        'odo_after',
        'kebutuhan_bbm_liter',
        'uraian',
        'tanggal_lpj',
        'due_date',
        'is_late',
        'days_late',
        'total_biaya_tol',
        'total_biaya_portir',
        'total_biaya_bbm',
        'total_biaya_lain',
        'total_pengeluaran',
        'uang_dari',
        'jumlah_diberikan',
        'pengembalian',
        'kekurangan',
        'budget_per_hari',
        'is_over_budget',
        'over_budget_reason',
        'catatan',
        'status',
        'approved_by_pengguna',
        'approved_pengguna_at',
        'approved_by_divisi_ga',
        'approved_divisi_ga_at',
        'approved_by_management',
        'approved_management_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'tanggal_lpj' => 'date',
        'due_date' => 'date',
        'is_late' => 'boolean',
        'total_biaya_tol' => 'decimal:2',
        'total_biaya_portir' => 'decimal:2',
        'total_biaya_bbm' => 'decimal:2',
        'total_biaya_lain' => 'decimal:2',
        'total_pengeluaran' => 'decimal:2',
        'jumlah_diberikan' => 'decimal:2',
        'odo_before' => 'decimal:2',
        'odo_after' => 'decimal:2',
        'kebutuhan_bbm_liter' => 'decimal:2',
        'pengembalian' => 'decimal:2',
        'kekurangan' => 'decimal:2',
        'budget_per_hari' => 'decimal:2',
        'is_over_budget' => 'boolean',
        'approved_pengguna_at' => 'datetime',
        'approved_divisi_ga_at' => 'datetime',
        'approved_management_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Gunakan slug (nomor LPJ URL-safe) sebagai route key agar ID tidak
     * tampil di URL. lpj_number berisi slash sehingga tidak aman untuk URL.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Resolusi route binding: utamakan slug, fallback ke ID agar URL lama
     * (mis. /lpj/{id}) tidak memunculkan 404.
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
     * Boot method untuk auto-calculate
     */
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lpj) {
            // ✅ Generate lpj_number otomatis
            if (empty($lpj->lpj_number)) {
                $lpj->lpj_number = static::generateLpjNumber();
            }

            // Slug URL-safe dari nomor LPJ (slash diganti strip)
            if (empty($lpj->slug)) {
                $lpj->slug = str_replace('/', '-', $lpj->lpj_number);
            }

            if (empty($lpj->tanggal_lpj)) {
                $lpj->tanggal_lpj = now();
            }

            if ($lpj->suratSpd) {
                // BUG-LPJ-001: budget harian = total dana / lama hari perjalanan.
                // Fallback ini dipakai jalur yang tidak mengirim budget_per_hari
                // eksplisit (mis. submit LPJ dari laporan harian).
                if (is_null($lpj->budget_per_hari) || (float) $lpj->budget_per_hari <= 0) {
                    $lpj->budget_per_hari = static::hitungBudgetPerHari($lpj->suratSpd);
                }

                $lpj->setDueDateFromSurat();
                $lpj->checkIfLate();
            }
        });
    }

    /**
     * Generate nomor LPJ unik, format: LPJ/YYYY/MM/0001
     */
    public static function generateLpjNumber(): string
    {
        $prefix = 'PTH.01/LPJ/SPD/' . now()->format('Y/m') . '/';

        $lastNumber = static::withTrashed()
            ->where('lpj_number', 'like', $prefix . '%')
            ->orderBy('lpj_number', 'desc')
            ->value('lpj_number');

        if ($lastNumber) {
            $lastSequence = (int) substr($lastNumber, strrpos($lastNumber, '/') + 1);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    // ============================================================
    // DUE DATE & LATE CALCULATION
    // ============================================================

    /**
     * ✅ SIMPLIFIED: Set due_date dari surat_spd.lpj_deadline
     * Tidak perlu logic kompleks untuk check extension
     * Karena setiap surat (original/extension) sudah punya lpj_deadline masing-masing
     */
    public function setDueDateFromSurat()
    {
        if (!$this->suratSpd) {
            return;
        }

        // Ambil langsung dari surat SPD
        // Setiap surat (baik original maupun extension) punya lpj_deadline sendiri
        if ($this->suratSpd->lpj_deadline) {
            $this->due_date = Carbon::parse($this->suratSpd->lpj_deadline)->toDateString();
        } else {
            // Fallback: calculate dari tanggal_kembali + 3 hari
            $this->due_date = Carbon::parse($this->suratSpd->tanggal_kembali)->addDays(3)->toDateString();
        }
    }

    /**
     * Check if submission is late
     */
    public function checkIfLate()
    {
        if (!$this->tanggal_lpj || !$this->due_date) {
            return;
        }

        $tanggalLpj = Carbon::parse($this->tanggal_lpj);
        $dueDate = Carbon::parse($this->due_date);

        if ($tanggalLpj->greaterThan($dueDate)) {
            $this->is_late = true;
            // Carbon 3: diffInDays() sudah bertanda. Hitung dari due_date ke
            // tanggal submit agar hasilnya POSITIF (sebelumnya bisa negatif
            // sehingga badge menampilkan "Terlambat -7 hari").
            $this->days_late = (int) $dueDate->startOfDay()->diffInDays($tanggalLpj->startOfDay());
        } else {
            $this->is_late = false;
            $this->days_late = 0;
        }
    }

    // ============================================================
    // TOTAL CALCULATIONS
    // ============================================================

    /**
     * Calculate totals from details
     */
    public function calculateTotals()
    {
        $this->total_biaya_tol = $this->details()
            ->where('tipe_penggunaan', 'Tol')
            ->sum('nominal');

        $this->total_biaya_portir = $this->details()
            ->whereIn('tipe_penggunaan', ['Parkir', 'Portir'])
            ->sum('nominal');

        $this->total_biaya_bbm = $this->details()
            ->where('tipe_penggunaan', 'BBM')
            ->sum('nominal');

        $this->total_biaya_lain = $this->details()
            ->whereIn('tipe_penggunaan', ['Lain-lain', 'Lainnya', 'Makan', 'Hotel'])
            ->sum('nominal');

        $this->total_pengeluaran = $this->total_biaya_tol +
                                   $this->total_biaya_portir +
                                   $this->total_biaya_bbm +
                                   $this->total_biaya_lain;

        $this->save();
    }

    /**
     * Calculate pengembalian atau kekurangan
     */
    public function calculateKeuangan()
    {
        $selisih = $this->jumlah_diberikan - $this->total_pengeluaran;

        if ($selisih >= 0) {
            $this->pengembalian = $selisih;
            $this->kekurangan = 0;
        } else {
            $this->pengembalian = 0;
            $this->kekurangan = abs($selisih);
        }

        $this->save();
    }

    /**
     * Budget harian LPJ = TOTAL DANA dibagi LAMA HARI PERJALANAN.
     *
     * BUG-LPJ-001: rumus lama membagi total dana dengan jumlah baris pencairan
     * (surat_spd_budgets). Satu kali pencairan untuk perjalanan beberapa hari
     * membuat budget harian terlalu besar sehingga status Over Budget tidak
     * pernah aktif meskipun pengeluaran melebihi plafon.
     *
     * @param  float|null  $totalDana  Total dana yang diberikan; null = hitung
     *                                 dari baris pencairan surat (fallback
     *                                 total_budget bila baris pencairan kosong).
     */
    public static function hitungBudgetPerHari(?SuratSpd $surat, ?float $totalDana = null): float
    {
        if (! $surat) {
            return 0.0;
        }

        $lamaHari = (int) $surat->lama_perjalanan_hari;

        if ($lamaHari <= 0) {
            return 0.0;
        }

        $total = $totalDana ?? (float) $surat->pencairans()->sum('nominal');

        if ($total <= 0) {
            $total = (float) ($surat->total_budget ?? 0);
        }

        return $total > 0 ? $total / $lamaHari : 0.0;
    }

    /**
     * Validate budget per hari
     */
    public function validateBudget()
    {
        if (!$this->suratSpd) {
            return;
        }

        $budgetPerHari = (float) $this->budget_per_hari;

        // Budget opsional: bila belum ada dana/budget, jangan menandai
        // Over Budget (rata-rata > 0 tidak boleh dibandingkan dengan plafon 0).
        if ($budgetPerHari <= 0) {
            $this->is_over_budget = false;
            $this->over_budget_reason = null;
            $this->save();

            return;
        }

        // Hitung rata-rata pengeluaran per hari
        $jumlahHari = (int) $this->suratSpd->lama_perjalanan_hari;
        $rataRataPerHari = $jumlahHari > 0 ? (float) $this->total_pengeluaran / $jumlahHari : 0;

        // Check if over budget
        if ($rataRataPerHari > $budgetPerHari) {
            $this->is_over_budget = true;
        } else {
            $this->is_over_budget = false;
            $this->over_budget_reason = null;
        }

        $this->save();
    }

    /**
     * Get biaya per hari breakdown
     */
    public function getBiayaPerHariBreakdown()
    {
        $jumlahHari = $this->suratSpd ? $this->suratSpd->lama_perjalanan_hari : 1;

        return [
            'rata_rata_per_hari' => $this->total_pengeluaran / $jumlahHari,
            'budget_per_hari' => $this->budget_per_hari,
            'selisih_per_hari' => $this->budget_per_hari - ($this->total_pengeluaran / $jumlahHari),
            'is_over_budget' => $this->is_over_budget,
        ];
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

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_id');
    }

    public function details()
    {
        return $this->hasMany(LpjPenggantianDetail::class, 'lpj_penggantian_id')->orderBy('urutan');
    }

    /**
     * Struk bukti pengeluaran per item (dengan gambar, kategori & item_code)
     */
    public function strukItems()
    {
        return $this->hasMany(StrukItem::class, 'lpj_id')->orderBy('urutan');
    }

    public function approverPengguna()
    {
        return $this->belongsTo(User::class, 'approved_by_pengguna');
    }

    public function approverDivisiGa()
    {
        return $this->belongsTo(User::class, 'approved_by_divisi_ga');
    }

    public function approverManagement()
    {
        return $this->belongsTo(User::class, 'approved_by_management');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
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

    public function scopeOverBudget($query)
    {
        return $query->where('is_over_budget', true);
    }

    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    public function scopeOnTime($query)
    {
        return $query->where('is_late', false);
    }

    /**
     * Scope unit bisnis: LPJ milik unit tertentu (lewat surat SPD).
     * Null = tanpa scope (global / pusat) — melihat semua unit.
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return;
        }

        $query->whereHas('suratSpd', fn ($q) => $q->where('surat_spds.unit_id', $unitId));
    }

    /**
     * Apakah user boleh membuka detail LPJ ini (F-01 / BUG-SEC-001).
     *
     * Aturan:
     *   - pemilik LPJ (user_id) dan penumpang SPD -> boleh;
     *   - user global (Pusat/superadmin/developers) -> semua LPJ;
     *   - pengelola LPJ (GA/Manager/pencairan) -> hanya unitnya sendiri;
     *   - requester lain di unit yang sama -> tidak boleh (404), konsisten
     *     dengan cetak/export SPD & LPJ.
     */
    public function isVisibleToUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->user_id !== null && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        $surat = $this->suratSpd;

        // Penumpang SPD (termasuk lintas unit) & pengelola unit tetap boleh.
        if ($surat !== null && $surat->isVisibleToUser($user)) {
            return true;
        }

        $scope = UnitScope::for($user);

        if ($scope->isGlobal()) {
            return true;
        }

        $peranPengelola = [
            'lpj.validate', 'lpj.validate_management', 'lpj.pencairan', 'lpj.export',
        ];

        if (! $user->hasPermission($peranPengelola)) {
            return false;
        }

        return $scope->allowsUnit($surat?->unit_id);
    }

    // ============================================================
    // APPROVAL METHODS
    // ============================================================

    /**
     * Approve by Pengguna
     */
    public function approveByPengguna($userId)
    {
        $this->update([
            'approved_by_pengguna' => $userId,
            'approved_pengguna_at' => now(),
        ]);

        // Check if both approved
        $this->checkFullApproval();
    }

    /**
     * Approve by Divisi GA
     */
    public function approveByDivisiGa($userId)
    {
        $this->update([
            'approved_by_divisi_ga' => $userId,
            'approved_divisi_ga_at' => now(),
        ]);

        // Check if fully approved
        $this->checkFullApproval();
    }

    /**
     * Approve by Management (supervisor tertinggi)
     */
    public function approveByManagement($userId)
    {
        $this->update([
            'approved_by_management' => $userId,
            'approved_management_at' => now(),
        ]);

        // Check if fully approved
        $this->checkFullApproval();
    }

    /**
     * Check if fully approved — 3 tahap: User (penumpang), Divisi GA, dan
     * Management. Ketiganya wajib terisi (boleh oleh orang yang sama, tetap
     * dicatat terpisah untuk akuntabilitas).
     *
     * BUG-LPJ-003: sebelumnya status `approved` langsung di-set setelah User +
     * Divisi GA, sehingga antrean validasi final Management terlewati. Sekarang
     * LPJ tetap `pending` sampai Management menyetujui.
     *
     * Catatan kompatibilitas: LPJ lama yang sudah `approved` tidak dihitung
     * ulang dan tidak "mundur" — pengecekan hanya berjalan saat aksi approval.
     */
    private function checkFullApproval()
    {
        if ($this->approved_by_pengguna
            && $this->approved_by_divisi_ga
            && $this->approved_by_management) {
            $this->update(['status' => 'approved']);

            // Mark surat as completed
            if ($this->suratSpd) {
                $this->suratSpd->markAsCompleted();
            }
        }
    }

    /**
     * Reject LPJ
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
     * Simple approve (for superadmin - approve all at once)
     */
    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by_pengguna' => $userId,
            'approved_pengguna_at' => now(),
            'approved_by_divisi_ga' => $userId,
            'approved_divisi_ga_at' => now(),
            'approved_by_management' => $userId,
            'approved_management_at' => now(),
        ]);

        // Mark surat as completed
        if ($this->suratSpd) {
            $this->suratSpd->markAsCompleted();
        }
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get formatted total pengeluaran
     */
    public function getFormattedTotalPengeluaranAttribute(): string
    {
        return 'Rp ' . number_format($this->total_pengeluaran, 0, ',', '.');
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'pending' => 'bg-warning text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Unknown',
        };
    }

    /**
     * Check apakah LPJ ini untuk surat extension
     */
    public function isForExtension(): bool
    {
        return $this->suratSpd && $this->suratSpd->is_extension;
    }

    /**
     * Get original surat (jika ini LPJ untuk extension)
     */
    public function getOriginalSurat()
    {
        if (!$this->suratSpd) {
            return null;
        }

        return $this->suratSpd->getOriginalSurat();
    }

    // ============================================================
    // HELPER METHODS - EXPORT / CETAK GABUNGAN
    // ============================================================

    /**
     * Total jarak tempuh SPD dari selisih odometer.
     * Return null jika salah satu odometer belum diisi.
     */
    public function getTotalJarakTempuh(): ?float
    {
        if ($this->odo_before === null || $this->odo_after === null) {
            return null;
        }

        return (float) $this->odo_after - (float) $this->odo_before;
    }

    /**
     * Label kendaraan untuk dokumen cetak: "<plat>-<merk/model>".
     * Contoh: "B 1234 XYZ - Toyota Avanza"
     */
    public function getKendaraanLabelAttribute(): string
    {
        $vehicle = $this->vehicle ?? $this->suratSpd?->vehicle;

        if (! $vehicle) {
            return $this->nomor_polisi ?? '-';
        }

        return $vehicle->dropdown_label ?? ($this->nomor_polisi ?? '-');
    }

    /**
     * Kolom biaya pada dokumen cetak (Lembar Persetujuan & Lembar Ringkasan).
     * Urutannya mengikuti header dokumen:
     * Tol → Parkir → BBM → Makan → Hotel → Lain-lain.
     */
    public const KOLOM_BIAYA = ['Tol', 'Parkir', 'BBM', 'Makan', 'Hotel', 'Lainnya'];

    /**
     * Sumber baris biaya untuk dokumen cetak.
     *
     * Basis utama = `lpj_penggantian_details`, karena tabel itulah yang
     * menyimpan angka resmi yang dipakai menghitung `total_pengeluaran`.
     * Setiap detail diperkaya (kategori, tanggal, foto) oleh `struk_items`
     * yang tertaut, sehingga nominal pada dokumen tidak pernah menyimpang dari
     * total LPJ. Bila LPJ dibuat lewat jalur lama yang tidak membuat detail,
     * baris disusun dari `struk_items` (kategori biaya saja).
     *
     * @return \Illuminate\Support\Collection<int, array{tanggal: ?\Carbon\Carbon, kategori: string, nominal: float, item: ?StrukItem}>
     */
    private function biayaRows(): \Illuminate\Support\Collection
    {
        $details = $this->details;

        if ($details->isNotEmpty()) {
            $strukPerDetail = $this->strukItems
                ->filter(fn ($item) => $item->lpj_detail_id !== null)
                ->keyBy('lpj_detail_id');

            return $details->map(function ($detail) use ($strukPerDetail) {
                /** @var StrukItem|null $struk */
                $struk = $strukPerDetail->get($detail->id);

                $kategori = $struk?->struk_kategori
                    ?? ($detail->tipe_penggunaan === 'Lain-lain' ? 'Lainnya' : $detail->tipe_penggunaan);

                return [
                    'tanggal'  => $struk?->tanggal ?? $detail->tanggal,
                    'kategori' => $kategori,
                    'nominal'  => (float) ($struk?->nominal ?? $detail->nominal),
                    'item'     => $struk,
                ];
            })->values();
        }

        return $this->strukItems
            ->whereIn('struk_kategori', self::KOLOM_BIAYA)
            ->map(fn ($item) => [
                'tanggal'  => $item->tanggal,
                'kategori' => $item->struk_kategori,
                'nominal'  => (float) $item->nominal,
                'item'     => $item,
            ])->values();
    }

    /**
     * Total nominal per kategori untuk dokumen cetak (pengeluaran saja, tanpa Tambahan).
     *
     * @return array<string, float> ['Tol' => .., 'Parkir' => .., 'BBM' => .., 'Makan' => .., 'Hotel' => .., 'Lainnya' => ..]
     */
    public function getSpendingPerKategori(): array
    {
        $spending = array_fill_keys(self::KOLOM_BIAYA, 0.0);

        foreach ($this->biayaRows() as $row) {
            if (array_key_exists($row['kategori'], $spending)) {
                $spending[$row['kategori']] += $row['nominal'];
            }
        }

        return $spending;
    }

    /**
     * Pivot rincian penggunaan per tanggal untuk tabel cetak.
     *
     * Dipakai oleh dua halaman dokumen sekaligus:
     *   - Lembar Ringkasan Penggunaan Biaya Perjalanan Dinas (blok per tanggal)
     *   - Lembar Persetujuan Penggantian Pertanggungjawaban (tabel per tanggal)
     *
     * Format: [
     *   '2026-08-01' => [
     *      'tanggal' => Carbon, 'Tol' => .., 'Parkir' => .., 'BBM' => ..,
     *      'Makan' => .., 'Hotel' => .., 'Lainnya' => .., 'subtotal' => ..,
     *      'items' => Collection,
     *   ], ...
     * ]
     *
     * @return array<string, array<string, mixed>>
     */
    public function getRincianPerTanggal(): array
    {
        $rows = [];

        foreach ($this->biayaRows() as $biaya) {
            if ($biaya['kategori'] === 'Tambahan') {
                continue; // dokumentasi, bukan pengeluaran
            }

            $tanggal = $biaya['tanggal'];
            $key = $tanggal ? $tanggal->format('Y-m-d') : 'tanpa-tanggal';

            if (! isset($rows[$key])) {
                $rows[$key] = array_merge([
                    'tanggal'  => $tanggal,
                    'subtotal' => 0.0,
                    'items'    => collect(),
                ], array_fill_keys(self::KOLOM_BIAYA, 0.0));
            }

            if (array_key_exists($biaya['kategori'], $rows[$key])) {
                $rows[$key][$biaya['kategori']] += $biaya['nominal'];
                $rows[$key]['subtotal'] += $biaya['nominal'];
            }

            if ($biaya['item']) {
                $rows[$key]['items']->push($biaya['item']);
            }
        }

        ksort($rows);

        return $rows;
    }

    /**
     * Grand total dari seluruh baris rincian per tanggal.
     */
    public function getGrandTotalRincian(): float
    {
        return (float) collect($this->getRincianPerTanggal())->sum('subtotal');
    }

    /**
     * Struk items yang diurutkan sesuai urutan kategori untuk lampiran foto:
     * Tol -> BBM -> Parkir -> Makan -> Hotel -> Lainnya -> Tambahan.
     */
    public function getStrukItemsOrderedForPhotos()
    {
        $order = ['Tol', 'BBM', 'Parkir', 'Makan', 'Hotel', 'Lainnya', 'Tambahan'];

        return $this->strukItems
            ->sortBy(fn ($item) => array_search($item->struk_kategori, $order, true) ?? 99)
            ->values();
    }

    // ============================================================
    // HELPER METHODS - LAMPIRAN PEMBERIAN DANA (PENCAIRAN)
    // ============================================================

    /**
     * Baris pencairan dana perjalanan dinas (dari surat_spd_budgets).
     * Diurutkan sesuai urutan dokumen.
     */
    public function getPencairanRows()
    {
        $surat = $this->suratSpd;

        if (! $surat) {
            return collect();
        }

        return $surat->pencairans;
    }

    /**
     * Total pencairan dana SPD (akumulasi baris surat_spd_budgets).
     * Menjadi sumber nilai "Jumlah Diberikan" pada LPJ.
     */
    public function getTotalPencairanAttribute(): float
    {
        return (float) $this->getPencairanRows()->sum('nominal');
    }

    /**
     * Nilai "Jumlah Diberikan" untuk form/tampilan.
     *
     * Nilai tersimpan diutamakan bila sudah diisi (> 0) agar edit LPJ lama
     * tidak menimpa nilai hasil koreksi GA. Bila masih 0 (belum diisi), nilai
     * awal diambil dari total pencairan dana SPD yang sudah disetujui.
     */
    public function getJumlahDiberikanEfektifAttribute(): float
    {
        $tersimpan = (float) $this->jumlah_diberikan;

        return $tersimpan > 0 ? $tersimpan : $this->total_pencairan;
    }

    /**
     * Balance Awal — total dana yang sudah ditransferkan/diberikan.
     *
     * Diambil dari akumulasi baris pencairan (surat_spd_budgets). Bila surat
     * belum punya baris pencairan sama sekali, fallback ke jumlah_diberikan
     * yang diisi manual oleh GA.
     */
    public function getBalanceAwalAttribute(): float
    {
        $totalPencairan = (float) $this->getPencairanRows()->sum('nominal');

        if ($totalPencairan > 0) {
            return $totalPencairan;
        }

        return (float) $this->jumlah_diberikan;
    }

    /**
     * Balance Akhir — sisa uang kas/transfer setelah dikurangi pengeluaran.
     * Nilai negatif berarti driver menombok (kekurangan).
     */
    public function getBalanceAkhirAttribute(): float
    {
        return $this->getBalanceAwalAttribute() - (float) $this->total_pengeluaran;
    }
}
