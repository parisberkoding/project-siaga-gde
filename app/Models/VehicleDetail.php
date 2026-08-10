<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleDetail extends Model
{
    use HasFactory;

    protected $table = 'vehicle_details';

    protected $fillable = [
        'vehicle_and_tools_consumer_id',
        'site_id',
        'jenis_kendaraan',
        'merk',
        'model',
        'tahun_pembuatan',
        'nomor_rangka',
        'nomor_mesin',
        'warna',
        'kapasitas_penumpang',
        'kapasitas_bahan_bakar',
        'jenis_bahan_bakar',
        'volume_tangki',
        'nominal_full_tank',
        'kapasitas_bbm_tahunan',
        'nominal_spd_default',
        'foto',
        'catatan',
    ];

    protected $casts = [
        'nominal_full_tank' => 'decimal:2',
        'kapasitas_bbm_tahunan' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_and_tools_consumer_id');
    }

    /**
     * Unit asal kendaraan (site_id)
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
