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
     * Scope unit bisnis: bukti transaksi milik unit tertentu (lewat permohonan).
     * Null = tanpa scope (global / pusat) — melihat semua unit.
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return;
        }

        $query->whereHas('permohonan', fn ($q) => $q->where('permohonans.unit_id', $unitId));
    }
}
