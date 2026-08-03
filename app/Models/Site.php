<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
