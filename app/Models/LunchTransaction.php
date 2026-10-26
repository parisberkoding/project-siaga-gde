<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LunchTransaction extends Model
{
    protected $fillable = [
        'transaction_date',
        'site_id',
        'employee_default_id',
        'employee_name',
        'division',
        'position',
        'raw_alias',
        'raw_position',
        'raw_division',
        'confidence_score',
        'resolution_method',
        'tomorrow_site_id',
        'confirmation_token',
        'source',
        'scanned_at',
        'dine_type',
        'institution',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'confidence_score' => 'decimal:2',
        'scanned_at'       => 'datetime',
    ];

    protected $hidden = [
        'confirmation_token',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeDefault::class, 'employee_default_id');
    }

    public function tomorrowSite()
    {
        return $this->belongsTo(Site::class, 'tomorrow_site_id');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('transaction_date', $date);
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('employee_default_id');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('employee_default_id');
    }

    /**
     * Scope unit bisnis: transaksi makan siang di salah satu site dalam unit.
     * Null = tanpa scope (global / pusat).
     */
    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->whereIn('site_id', $siteIds);
    }

    public function isResolved(): bool
    {
        return $this->employee_default_id !== null;
    }

    public function resolutionBadgeClass(): string
    {
        return match ($this->resolution_method) {
            'alias_exact', 'name_exact' => 'success',
            'historical'                => 'info',
            'fuzzy'                     => 'warning',
            'manual'                    => 'primary',
            default                     => 'danger',
        };
    }
}
