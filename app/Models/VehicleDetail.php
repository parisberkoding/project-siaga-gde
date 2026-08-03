<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleDetail extends Model
{
    protected $table = 'vehicle_details';

    protected $fillable = [
        'vehicle_and_tools_consumer_id',
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
        'foto',
        'catatan',
    ];

    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_and_tools_consumer_id');
    }
}
