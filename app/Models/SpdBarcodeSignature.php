<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SuratSpd;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpdBarcodeSignature extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'spd_barcode_signatures';

    protected $fillable = [
        'spd_id',
        'signature_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relasi ke SuratSpd
     */
    public function suratspd()
    {
        return $this->belongsTo(SuratSpd::class, 'spd_id');
    }
}
