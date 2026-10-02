<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approved_Request extends Model  // Ubah dari Approved_Request
{
    protected $table = 'approved_requests';  // Ubah dari approved__requests

    protected $fillable = [
        'permohonan_id',
        'approval_date',
        'voucher_code',
        'valid_until',
        'approved_amount',
        'authorizer_id',
        'approval_notes',
        'status',
        'unit_id',
    ];

    // Relasi ke Permohonan
    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    // Relasi ke User (Authorizer)
    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorizer_id');
    }

    // Relasi ke Unit (scope data antar unit)
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope unit bisnis: voucher milik unit tertentu.
     * Null = tanpa scope (global / pusat) — melihat semua unit.
     */
    public function scopeInUnit($query, ?int $unitId)
    {
        if ($unitId === null) {
            return;
        }

        $query->where('approved_requests.unit_id', $unitId);
    }
}
