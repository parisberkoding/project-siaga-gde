<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratSpdDetail extends Model
{
    use HasFactory;

    // ✅ Sesuaikan dengan nama tabel di database kamu
    protected $table = 'spd_surat_details';

    protected $fillable = [
        'surat_spd_id',
        'nama_lengkap',
        'user_id',
        'urutan',
    ];

    /**
     * Relationships
     */
    public function suratSpd()
    {
        return $this->belongsTo(SuratSpd::class, 'surat_spd_id');
    }

    /**
     * Akun user penumpang (nullable — boleh tidak terdaftar di sistem).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
