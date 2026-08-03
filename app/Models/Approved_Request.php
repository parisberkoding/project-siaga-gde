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

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
