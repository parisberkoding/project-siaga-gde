<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehiclePart extends Model
{
    use HasFactory;

    protected $table = 'vehicle_parts';

    protected $fillable = [
        'part_code',
        'name',
        'category',
        'checking_group',
        'check_frequency',
        'status_type',
        'standard_description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Route model binding: pakai part_code sebagai slug
     */
    public function getRouteKeyName()
    {
        return 'part_code';
    }

    // ========================================
    // SCOPES
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoutine($query)
    {
        return $query->where('checking_group', 'routine');
    }

    public function scopeBody($query)
    {
        return $query->where('checking_group', 'body');
    }

    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('check_frequency', $frequency);
    }

    public function scopeDaily($query)
    {
        return $query->where('check_frequency', 'daily');
    }

    public function scopeWeekly($query)
    {
        return $query->where('check_frequency', 'weekly');
    }

    public function scopeMonthly($query)
    {
        return $query->where('check_frequency', 'monthly');
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function checkingItemResults()
    {
        return $this->hasMany(VehicleCheckingItemResult::class, 'vehicle_part_id');
    }

    public function replacementDetails()
    {
        return $this->hasMany(VehiclePartsReplacementDetail::class, 'vehicle_part_id');
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Cek apakah ini item binary (V/X) atau scale (Baik/Cukup/Kurang/Tidak)
     */
    public function isBinary()
    {
        return $this->status_type === 'binary';
    }

    public function isScale()
    {
        return $this->status_type === 'scale';
    }

    /**
     * Get opsi status berdasarkan tipe
     */
    public function getStatusOptions()
    {
        if ($this->isBinary()) {
            return ['ok' => 'V (OK)', 'ng' => 'X (NG)'];
        }

        return [
            'baik' => 'Baik',
            'cukup' => 'Cukup',
            'kurang' => 'Kurang',
            'tidak' => 'Tidak',
        ];
    }
}
