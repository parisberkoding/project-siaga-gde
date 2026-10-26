<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoice vendor per periode.
 *
 * calculated_amount = total_portion × price_per_portion.
 * total_amount      = nominal final (boleh dikoreksi manual).
 * Sisa bayar dihitung dari payments, bukan disimpan, supaya tidak pernah
 * berbeda dengan riwayat pembayaran.
 */
class LunchVendorInvoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'lunch_vendor_invoices';

    protected $fillable = [
        'vendor_id',
        'unit_id',
        'site_id',
        'period_start',
        'period_end',
        'total_portion',
        'price_per_portion',
        'calculated_amount',
        'total_amount',
        'status',
        'due_date',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'period_start'       => 'date',
        'period_end'         => 'date',
        'due_date'           => 'date',
        'total_portion'      => 'integer',
        'price_per_portion'  => 'decimal:2',
        'calculated_amount'  => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'approved_at'        => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(LunchVendor::class, 'vendor_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function payments()
    {
        return $this->hasMany(LunchVendorPayment::class, 'lunch_vendor_invoice_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidAmount(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function remainingAmount(): float
    {
        return max(0, (float) $this->total_amount - $this->paidAmount());
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->remainingAmount() > 0
            && $this->due_date->isBefore(today())
            && ! in_array($this->status, [self::STATUS_PAID, self::STATUS_CANCELLED], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_SUBMITTED => 'Diajukan',
            self::STATUS_APPROVED  => 'Disetujui',
            self::STATUS_PARTIAL   => 'Dibayar Sebagian',
            self::STATUS_PAID      => 'Lunas',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default                 => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'secondary',
            self::STATUS_SUBMITTED => 'info',
            self::STATUS_APPROVED  => 'primary',
            self::STATUS_PARTIAL   => 'warning',
            self::STATUS_PAID      => 'success',
            self::STATUS_CANCELLED => 'danger',
            default                 => 'secondary',
        };
    }

    public function scopeInUnit($query, ?array $siteIds)
    {
        if ($siteIds === null) {
            return;
        }

        $query->whereIn('site_id', $siteIds);
    }
}
