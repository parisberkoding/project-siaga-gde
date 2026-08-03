<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleBarcode extends Model
{
    use HasFactory;

    protected $table = 'vehicle_barcodes';

    protected $fillable = [
        'vehicle_and_tools_consumer_id',
        'barcode_path',
    ];

    /**
     * Relasi ke kendaraan pemilik barcode
     */
    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_and_tools_consumer_id');
    }

    /**
     * Normalize barcode_path: strip leading 'public/' if present.
     */
    protected function normalizedPath(): string
    {
        return ltrim(preg_replace('#^public/#', '', $this->barcode_path), '/');
    }

    /**
     * Path absolut file di filesystem
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/public/' . $this->normalizedPath());
    }

    /**
     * URL publik untuk ditampilkan/diunduh
     */
    public function getUrlAttribute(): string
    {
        return route('storage.serve', ['path' => $this->normalizedPath()]);
    }
}

