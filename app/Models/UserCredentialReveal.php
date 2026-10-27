<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail pembukaan kredensial operasional oleh superadmin.
 *
 * Password disimpan terenkripsi (APP_KEY) karena harus bisa diberikan
 * secara offline. Setiap kali dibuka, catat siapa & kapan — supaya
 * penyalahgunaan bisa ditelusuri.
 */
class UserCredentialReveal extends Model
{
    protected $table = 'user_credential_reveals';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'revealed_by',
        'ip_address',
        'user_agent',
        'revealed_at',
    ];

    protected $casts = [
        'revealed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function revealedBy()
    {
        return $this->belongsTo(User::class, 'revealed_by');
    }
}
