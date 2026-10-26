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
        'nik',
        'employee_name',
        'slug',
        'division',
        'employment_type',
        'position',
        'default_site_id',
        'unit_id',
        'is_active',
        'verification_status',
        'source',
        'created_by',
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

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Unit efektif karyawan: kolom unit_id, fallback ke unit site default.
     */
    public function resolvedUnitId(): ?int
    {
        return $this->unit_id ?? $this->defaultSite?->unit_id;
    }

    public function resolvedUnit(): ?Unit
    {
        $id = $this->resolvedUnitId();

        return $id ? ($this->unit ?? Unit::find($id)) : null;
    }

    /**
     * Site yang boleh dipilih karyawan ini untuk "besok makan dimana".
     *
     * Hanya site di unitnya sendiri. Bila unit belum diketahui, fallback ke
     * site default (bukan seluruh site) supaya tidak ada kebocoran lintas unit.
     *
     * @return \Illuminate\Support\Collection<int, Site>
     */
    public function selectableSites()
    {
        $unitId = $this->resolvedUnitId();

        if ($unitId) {
            return Site::active()->where('unit_id', $unitId)->orderBy('name')->get();
        }

        if ($this->default_site_id) {
            return Site::active()->whereKey($this->default_site_id)->get();
        }

        return collect();
    }

    public function isPendingVerification(): bool
    {
        return $this->verification_status === 'pending';
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
     * Scope unit bisnis: karyawan yang unit/default site-nya ada di dalam unit.
     * Null = tanpa scope (global / pusat).
     */
    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $unitIds = Site::whereIn('id', $siteIds)->pluck('unit_id')->filter()->unique()->values();

        $query->where(function ($q) use ($siteIds, $unitIds) {
            $q->whereIn('default_site_id', $siteIds);

            if ($unitIds->isNotEmpty()) {
                $q->orWhereIn('unit_id', $unitIds);
            }
        });
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePendingVerification($query)
    {
        return $query->where('verification_status', 'pending');
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
