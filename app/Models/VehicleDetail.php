<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleDetail extends Model
{
    use HasFactory;

    protected $table = 'vehicle_details';

    /**
     * Status kepemilikan aset kendaraan/alat.
     * SEWA dipakai untuk kendaraan rental (sewa harian/bulanan).
     */
    public const STATUS_MILIK_SENDIRI = 'MILIK_SENDIRI';

    public const STATUS_SEWA = 'SEWA';

    public const STATUS_PINJAM_PAKAI = 'PINJAM_PAKAI';

    public const STATUS_LEASING = 'LEASING';

    /**
     * Label status kepemilikan untuk dropdown & tampilan.
     *
     * @return array<string, string>
     */
    public static function statusKepemilikanOptions(): array
    {
        return [
            self::STATUS_MILIK_SENDIRI => 'Milik Sendiri (Aset)',
            self::STATUS_SEWA => 'Sewa / Rental',
            self::STATUS_PINJAM_PAKAI => 'Pinjam Pakai',
            self::STATUS_LEASING => 'Leasing',
        ];
    }

    /**
     * Jenis kendaraan yang tersedia di dropdown.
     *
     * @return array<int, string>
     */
    public static function jenisKendaraanOptions(): array
    {
        return ['SUV', 'MPV', 'Sedan', 'Pickup', 'Truck', 'Bus', 'Motor', 'Mini Bus', 'Double Cabin', 'Alat Berat'];
    }

    protected $fillable = [
        'vehicle_and_tools_consumer_id',
        'site_id',
        'jenis_kendaraan',
        'merk',
        'model',
        'tahun_pembuatan',
        'nomor_rangka',
        'nomor_mesin',
        'nomor_bpkb',
        'nomor_stnk',
        'nomor_kir',
        'pajak_berlaku_sampai',
        'kir_berlaku_sampai',
        'kapasitas_mesin',
        'odometer',
        'status_kepemilikan',
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
        'pajak_berlaku_sampai' => 'date',
        'kir_berlaku_sampai' => 'date',
        'tahun_pembuatan' => 'integer',
        'kapasitas_mesin' => 'integer',
        'odometer' => 'integer',
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

    /**
     * Label status kepemilikan yang siap tampil.
     */
    public function getStatusKepemilikanLabelAttribute(): ?string
    {
        if (! $this->status_kepemilikan) {
            return null;
        }

        return self::statusKepemilikanOptions()[$this->status_kepemilikan]
            ?? $this->status_kepemilikan;
    }

    /**
     * Status masa berlaku pajak: ok | soon (<=30 hari) | expired | null.
     */
    public function getPajakStatusAttribute(): ?string
    {
        return $this->expiryStatus($this->pajak_berlaku_sampai);
    }

    /**
     * Status masa berlaku KIR: ok | soon (<=30 hari) | expired | null.
     */
    public function getKirStatusAttribute(): ?string
    {
        return $this->expiryStatus($this->kir_berlaku_sampai);
    }

    private function expiryStatus(?\Illuminate\Support\Carbon $date): ?string
    {
        if (! $date) {
            return null;
        }

        if ($date->isPast()) {
            return 'expired';
        }

        return $date->lessThanOrEqualTo(now()->addDays(30)) ? 'soon' : 'ok';
    }
}
