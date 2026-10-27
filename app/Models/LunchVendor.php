<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Vendor catering.
 *
 * Vendor dapat melayani satu unit penuh (site_id NULL) atau satu site
 * tertentu. Harga per porsi dipakai sebagai default saat invoice dibuat,
 * tetapi nominal invoice tetap bisa dikoreksi manual.
 */
class LunchVendor extends Model
{
    use HasFactory;

    protected $table = 'lunch_vendors';

    protected $fillable = [
        'unit_id',
        'site_id',
        'name',
        'contact_person',
        'phone',
        'price_per_portion',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'price_per_portion' => 'decimal:2',
        'is_active'         => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function invoices()
    {
        return $this->hasMany(LunchVendorInvoice::class, 'vendor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->where(function ($q) use ($siteIds) {
            $q->whereIn('site_id', $siteIds)
                ->orWhere(function ($q2) use ($siteIds) {
                    $q2->whereNull('site_id')
                        ->whereIn('unit_id', Site::whereIn('id', $siteIds)->pluck('unit_id'));
                });
        });
    }
}
