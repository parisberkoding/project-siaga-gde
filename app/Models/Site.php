<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    public const TYPE_OFFICE = 'office';
    public const TYPE_POWER_PLANT = 'power_plant';
    public const TYPE_BASECAMP = 'basecamp';
    public const TYPE_RESERVOIR = 'reservoir';
    public const TYPE_WELL = 'well';
    public const TYPE_OTHER = 'other';

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_POWER_PLANT => 'Power Plant',
            self::TYPE_BASECAMP    => 'Basecamp',
            self::TYPE_OFFICE      => 'Kantor',
            self::TYPE_RESERVOIR   => 'Reservoir',
            self::TYPE_WELL        => 'Sumur / Well',
            self::TYPE_OTHER       => 'Lainnya',
        ];
    }

    protected $fillable = [
        'code',
        'name',
        'type',
        'unit_id',
        'province_slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? 'Lainnya';
    }

    /**
     * Label hierarkis: "Unit Patuha › Basecamp Patuha".
     */
    public function hierarchicalLabel(): string
    {
        $unit = $this->unit?->name;

        return $unit ? "{$unit} › {$this->name}" : $this->name;
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'default_site_id');
    }

    public function employeeDefaults()
    {
        return $this->hasMany(EmployeeDefault::class, 'default_site_id');
    }

    public function predictedForecasts()
    {
        return $this->hasMany(LunchForecast::class, 'predicted_site_id');
    }

    public function actualForecasts()
    {
        return $this->hasMany(LunchForecast::class, 'actual_site_id');
    }

    public function lunchTransactions()
    {
        return $this->hasMany(LunchTransaction::class);
    }

    public function guestReservations()
    {
        return $this->hasMany(LunchGuestReservation::class);
    }

    public function distributions()
    {
        return $this->hasMany(LunchDistribution::class);
    }

    /**
     * Kendaraan yang ber-unit di site ini (lewat vehicle_details.site_id)
     */
    public function vehicleDetails()
    {
        return $this->hasMany(VehicleDetail::class, 'site_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
