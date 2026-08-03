<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LunchGuestReservation extends Model
{
    use HasFactory;

    protected $table = 'lunch_guest_reservations';

    protected $fillable = [
        'reservation_date',
        'guest_name',
        'institution',
        'guest_count',
        'site_id',
        'notes',
        'status',
        'confirmed_at',
        'created_by',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'confirmed_at'     => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Apakah tamu ini perlu dikonfirmasi H-2 (status masih pending & 2 hari lagi).
     */
    public function needsH2Confirmation(): bool
    {
        return $this->status === 'pending'
            && $this->reservation_date->diffInDays(now()->startOfDay()) <= 2
            && $this->reservation_date->gte(now()->startOfDay());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'Menunggu Konfirmasi',
            'confirmed' => 'Terkonfirmasi',
            'cancelled' => 'Dibatalkan',
            default     => $this->status,
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'   => 'warning',
            'confirmed' => 'success',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}
