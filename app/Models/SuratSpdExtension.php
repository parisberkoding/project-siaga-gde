<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HasSafeSlugRouteKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuratSpdExtension extends Model
{
    use HasFactory, HasSafeSlugRouteKey;

    protected $table = 'surat_spd_extensions';

    protected $fillable = [
        'slug',
        'extension_number',
        'original_surat_id',
        'extension_surat_id',
        'nomor_surat_extension',
        'tanggal_kembali_original',
        'tanggal_kembali_baru',
        'durasi_tambahan_hari',
        'budget_tambahan',
        'alasan_perpanjangan',
        'status',
        // Approval
        'approved_by',
        'approved_at',
        // Rejection - ✅ ADDED
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        // Acknowledge
        'is_acknowledged',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledgement_note',
        // Additional
        'catatan',
    ];

    protected $casts = [
        'tanggal_kembali_original' => 'date',
        'tanggal_kembali_baru' => 'date',
        'budget_tambahan' => 'decimal:2',
        'is_acknowledged' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',      // ✅ ADDED
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Boot method untuk auto-generate slug URL-safe.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($extension) {
            if (empty($extension->extension_number) && $extension->nomor_surat_extension) {
                $extension->extension_number = 'EXT-'.str_replace('/', '-', $extension->nomor_surat_extension);
            }

            if (empty($extension->slug) && $extension->nomor_surat_extension) {
                $extension->slug = str_replace('/', '-', $extension->nomor_surat_extension);
            }
        });
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    /**
     * Gunakan slug (nomor surat extension URL-safe) sebagai route key agar
     * ID tidak tampil di URL. nomor_surat_extension berisi slash.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Surat original yang diperpanjang
     */
    public function originalSurat()
    {
        return $this->belongsTo(SuratSpd::class, 'original_surat_id');
    }

    /**
     * Surat extension (surat perpanjangan baru)
     */
    public function extensionSurat()
    {
        return $this->belongsTo(SuratSpd::class, 'extension_surat_id');
    }

    /**
     * User yang approve
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User yang reject
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * User yang acknowledge
     */
    public function acknowledger()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
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

    public function scopeAcknowledged($query)
    {
        return $query->where('is_acknowledged', true);
    }

    public function scopeNotAcknowledged($query)
    {
        return $query->where('is_acknowledged', false);
    }

    /**
     * Scope: Approved tapi belum acknowledged
     */
    public function scopeNeedAcknowledge($query)
    {
        return $query->where('status', 'approved')
            ->where('is_acknowledged', false);
    }

    /**
     * Scope unit bisnis: delegasikan ke surat extension-nya (unit_id SuratSpd).
     * Null = tanpa scope (global / pusat).
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return;
        }

        $query->whereHas('extensionSurat', fn ($q) => $q->where('surat_spds.unit_id', $unitId));
    }

    // ============================================================
    // STATUS METHODS
    // ============================================================

    /**
     * Approve extension.
     *
     * Selain mengubah status, perpanjangan dipropagasikan ke chain:
     *   - surat sebelumnya (immediate previous) dan root chain
     *     (tanggal_kembali, lpj_deadline, extension_count);
     *   - surat extension baru ikut di-approve.
     *
     * PENTING: budget TIDAK lagi ditambahkan otomatis dari durasi perpanjangan.
     * Driver hanya mengajukan perpanjangan; Admin GA unit yang memverifikasi
     * lalu mencatat sendiri pemberian dana perpanjangan (kapan & berapa).
     *
     * Idempotent: pemanggilan kedua tidak menerapkan propagation dua kali.
     */
    public function approve($userId)
    {
        if ($this->status === 'approved') {
            return;
        }

        $tanggalKembaliBaru = $this->tanggal_kembali_baru
            ? Carbon::parse($this->tanggal_kembali_baru)
            : null;

        DB::transaction(function () use ($userId, $tanggalKembaliBaru) {
            $this->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            // Immediate previous surat (fallback ke parent surat extension
            // untuk record lama yang masih menunjuk root).
            $extensionSurat = $this->extensionSurat()->first();

            $previous = $this->originalSurat()->first()
                ?: $extensionSurat?->parentSurat()->first();

            $root = $previous?->getRootSurat();

            $targets = collect([$previous, $root])->filter()->unique('id');

            foreach ($targets as $target) {
                $data = [
                    'extension_count' => ((int) $target->extension_count) + 1,
                ];

                if ($tanggalKembaliBaru) {
                    $target->tanggal_kembali = $tanggalKembaliBaru->toDateString();
                    $target->calculateLpjDeadline();

                    $data['tanggal_kembali'] = $target->tanggal_kembali;
                    $data['lpj_deadline'] = $target->lpj_deadline;
                }

                $target->update($data);
            }

            if ($extensionSurat) {
                $extensionSurat->approve($userId);
            }
        });
    }

    /**
     * Reject extension
     * Juga update status surat extension
     */
    public function reject($userId, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $userId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Update surat extension juga
        if ($this->extensionSurat) {
            $this->extensionSurat->reject($userId, $reason);
        }
    }

    /**
     * Acknowledge extension (checklist sudah diproses)
     */
    public function acknowledge($userId, $note = null)
    {
        $this->update([
            'is_acknowledged' => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
            'acknowledgement_note' => $note,
        ]);
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Check apakah sudah approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check apakah pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check apakah rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Get formatted durasi tambahan
     */
    public function getFormattedDurasiAttribute(): string
    {
        return '+' . $this->durasi_tambahan_hari . ' hari';
    }

    /**
     * Get formatted budget tambahan
     */
    public function getFormattedBudgetTambahanAttribute(): string
    {
        return 'Rp ' . number_format($this->budget_tambahan, 0, ',', '.');
    }

    /**
     * Get status badge class (untuk view)
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
     * Get status label (untuk view)
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Approval',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Unknown',
        };
    }
}
