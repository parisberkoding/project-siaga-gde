<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratSpdFundAddition extends Model
{
    use HasFactory;

    protected $table = 'surat_spd_fund_additions';

    protected $fillable = [
        'surat_spd_id',
        'nominal_tambahan',
        'alasan',
        'bukti_transfer_path',
        'bank_reference',
        'created_by',
    ];

    protected $casts = [
        'nominal_tambahan' => 'decimal:2',
    ];

    public function suratSpd()
    {
        return $this->belongsTo(SuratSpd::class, 'surat_spd_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
