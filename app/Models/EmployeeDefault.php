<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmployeeDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'employee_name',
        'division',
        'position',
        'default_site_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function (self $employee) {
            $employee->slug = $employee->slug ?: static::generateUniqueSlug($employee->employee_name);
        });

        static::updating(function (self $employee) {
            if ($employee->isDirty('employee_name') && !$employee->isDirty('slug')) {
                $employee->slug = static::generateUniqueSlug($employee->employee_name, $employee->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $i = 1;
        while (static::where('slug', $slug)->where('id', '!=', $ignoreId)->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    /**
     * Akun login (opsional). Lunch Management tidak bergantung pada akun:
     * karyawan tanpa akun tetap tercatat di master ini.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function defaultSite()
    {
        return $this->belongsTo(Site::class, 'default_site_id');
    }

    public function aliases()
    {
        return $this->hasMany(EmployeeAlias::class);
    }

    public function transactions()
    {
        return $this->hasMany(LunchTransaction::class);
    }

    public function forecasts()
    {
        return $this->hasMany(LunchForecast::class);
    }

    public function learningProfiles()
    {
        return $this->hasMany(LearningProfile::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope unit bisnis: karyawan yang default site-nya ada di dalam unit.
     * Null = tanpa scope (global / pusat).
     */
    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->whereIn('default_site_id', $siteIds);
    }

    /**
     * Site dominan menurut Learning Engine (transaksi aktual terbanyak).
     */
    public function dominantLearningProfile(): ?LearningProfile
    {
        return $this->learningProfiles()
            ->orderByDesc('transaction_count')
            ->first();
    }
}
