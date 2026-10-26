<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DamagePhoto extends Model
{
    use HasFactory;

    protected $table = 'damage_photos';

    protected $fillable = [
        'vehicle_checking_detail_id',
        'photo',
        'zone',
        'description',
    ];

    public function vehicleCheckingDetail()
    {
        return $this->belongsTo(VehicleCheckingDetail::class, 'vehicle_checking_detail_id');
    }
}
