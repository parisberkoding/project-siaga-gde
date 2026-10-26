<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction_Proofs extends Model
{

    use hasFactory, SoftDeletes;

    protected $table = 'transaction_proofs';  // Perbaiki nama tabel

    protected $fillable = [
        'transaction_id',
        'req_id',
        'requester_id',
        'unit_id',
        'voucher_number',
        'purchase_datetime',
        'fuel_volume',
        'km_terakhir',
        'struk_bbm_path',
        'odometer_photo_path',
    ];

    protected $casts = [
        'purchase_datetime' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Bukti pembelian BBM mewarisi unit dari permohonan induknya.
     *
     * Diisi otomatis supaya setiap jalur pembuatan bukti (termasuk yang belum
     * ada hari ini) selalu punya penanda unit — tidak bergantung pada disiplin
     * pemanggil di controller.
     */
    protected static function booted(): void
    {
        static::creating(function (self $proof) {
            if ($proof->unit_id === null && $proof->req_id) {
                $proof->unit_id = Permohonan::whereKey($proof->req_id)->value('unit_id');
            }
        });
    }

    /**
     * Gunakan voucher_number sebagai route key agar ID tidak tampil di URL.
     */
    public function getRouteKeyName(): string
    {
        return 'voucher_number';
    }

    // Relasi ke Permohonan
    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'req_id');
    }

    // Relasi ke User (Requester)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Unit pemilik bukti transaksi.
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Scope unit bisnis: bukti transaksi milik unit tertentu.
     * Null = tanpa scope (global / pusat) — melihat semua unit.
     *
     * Memakai kolom `unit_id` langsung agar dapat diindeks, dengan fallback ke
     * relasi permohonan untuk baris lama yang belum ter-backfill.
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return;
        }

        $query->where(function ($q) use ($unitId) {
            $q->where('transaction_proofs.unit_id', $unitId)
                ->orWhere(function ($legacy) use ($unitId) {
                    $legacy->whereNull('transaction_proofs.unit_id')
                        ->whereHas('permohonan', fn ($p) => $p->where('permohonans.unit_id', $unitId));
                });
        });
    }
}
