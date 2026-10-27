<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pembelian manual yang menggantikan katering reguler pada satu tanggal
 * dan site (perayaan, pelayanan, apresiasi pegawai).
 */
class LunchCustomPurchase extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'lunch_custom_purchases';

    protected $fillable = [
        'unit_id',
        'site_id',
        'purchase_date',
        'title',
        'description',
        'vendor_name',
        'total_amount',
        'status',
        'replaces_catering',
        'receipt_path',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'purchase_date'     => 'date',
        'total_amount'      => 'decimal:2',
        'replaces_catering' => 'boolean',
        'approved_at'       => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function items()
    {
        return $this->hasMany(LunchCustomPurchaseItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeReplacesCatering($query)
    {
        return $query->where('replaces_catering', true)
            ->whereNotIn('status', [self::STATUS_CANCELLED]);
    }

    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->whereIn('site_id', $siteIds);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_APPROVED  => 'Disetujui',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default                 => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'secondary',
            self::STATUS_APPROVED  => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default                 => 'secondary',
        };
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = $this->items()->sum('subtotal');
        $this->save();
    }
}
