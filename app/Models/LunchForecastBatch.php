<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LunchForecastBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'forecast_date',
        'generated_by_type',
        'generated_by_user_id',
        'algorithm_version',
        'history_sample_size',
        'status',
        'finalized_at',
    ];

    protected $casts = [
        'forecast_date'       => 'date',
        'history_sample_size' => 'integer',
        'finalized_at'        => 'datetime',
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function forecasts()
    {
        return $this->hasMany(LunchForecast::class, 'forecast_batch_id');
    }

    public function distributions()
    {
        return $this->hasMany(LunchDistribution::class, 'forecast_batch_id');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }
}
