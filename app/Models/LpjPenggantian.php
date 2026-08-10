<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;  // ✅ ADDED: Missing import

class LpjPenggantian extends Model
{
    use HasFactory, SoftDeletes;

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
                if (is_null($lpj->budget_per_hari)) {
                    $lpj->budget_per_hari = $lpj->suratSpd->budget_per_hari ?? 0;
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
            $this->days_late = $tanggalLpj->diffInDays($dueDate);
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
            ->where('tipe_penggunaan', 'Lain-lain')
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
     * Validate budget per hari
     */
    public function validateBudget()
    {
        if (!$this->suratSpd) {
            return;
        }

        // Hitung rata-rata pengeluaran per hari
        $jumlahHari = $this->suratSpd->lama_perjalanan_hari;
        $rataRataPerHari = $jumlahHari > 0 ? $this->total_pengeluaran / $jumlahHari : 0;

        // Check if over budget
        if ($rataRataPerHari > $this->budget_per_hari) {
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

        // Check if both approved
        $this->checkFullApproval();
    }

    /**
     * Check if fully approved
     */
    private function checkFullApproval()
    {
        if ($this->approved_by_pengguna && $this->approved_by_divisi_ga) {
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
     * Simple approve (for superadmin - approve both at once)
     */
    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by_pengguna' => $userId,
            'approved_pengguna_at' => now(),
            'approved_by_divisi_ga' => $userId,
            'approved_divisi_ga_at' => now(),
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
     * Total nominal per kategori dari struk_items (pengeluaran saja, tanpa Tambahan).
     *
     * @return array<string, float> ['Tol' => .., 'BBM' => .., 'Parkir' => .., 'Lainnya' => ..]
     */
    public function getSpendingPerKategori(): array
    {
        $spending = ['Tol' => 0.0, 'BBM' => 0.0, 'Parkir' => 0.0, 'Lainnya' => 0.0];

        foreach ($this->strukItems as $item) {
            $kategori = $item->struk_kategori;

            if (array_key_exists($kategori, $spending)) {
                $spending[$kategori] += (float) $item->nominal;
            }
        }

        return $spending;
    }

    /**
     * Pivot rincian penggunaan per tanggal untuk tabel cetak.
     * Format: ['2026-08-01' => ['tanggal' => Carbon, 'Tol' => .., 'BBM' => .., 'Parkir' => .., 'Lainnya' => ..]]
     */
    public function getRincianPerTanggal(): array
    {
        $rows = [];

        foreach ($this->strukItems as $item) {
            $key = $item->tanggal ? $item->tanggal->format('Y-m-d') : 'tanpa-tanggal';

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'tanggal'  => $item->tanggal,
                    'Tol'      => 0.0,
                    'BBM'      => 0.0,
                    'Parkir'   => 0.0,
                    'Lainnya'  => 0.0,
                ];
            }

            if (array_key_exists($item->struk_kategori, $rows[$key])) {
                $rows[$key][$item->struk_kategori] += (float) $item->nominal;
            }
        }

        ksort($rows);

        return $rows;
    }

    /**
     * Struk items yang diurutkan sesuai urutan kategori untuk lampiran foto:
     * Tol -> BBM -> Parkir -> Lainnya -> Tambahan.
     */
    public function getStrukItemsOrderedForPhotos()
    {
        $order = ['Tol', 'BBM', 'Parkir', 'Lainnya', 'Tambahan'];

        return $this->strukItems
            ->sortBy(fn ($item) => array_search($item->struk_kategori, $order, true) ?? 99)
            ->values();
    }
}
