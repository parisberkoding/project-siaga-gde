<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LpjPenggantianDetail extends Model
{
    use HasFactory;

    protected $table = 'lpj_penggantian_details';

    protected $fillable = [
        'lpj_penggantian_id',
        'tanggal',
        'tipe_penggunaan',
        'nominal',
        'keterangan',
        'urutan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'decimal:2',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Setelah create/update/delete, update totals di header
        static::saved(function ($detail) {
            if ($detail->lpjPenggantian) {
                $detail->lpjPenggantian->calculateTotals();
                $detail->lpjPenggantian->calculateKeuangan();
                $detail->lpjPenggantian->validateBudget();
            }
        });

        static::deleted(function ($detail) {
            if ($detail->lpjPenggantian) {
                $detail->lpjPenggantian->calculateTotals();
                $detail->lpjPenggantian->calculateKeuangan();
                $detail->lpjPenggantian->validateBudget();
            }
        });
    }

    /**
     * Relationships
     */
    public function lpjPenggantian()
    {
        return $this->belongsTo(LpjPenggantian::class, 'lpj_penggantian_id');
    }

    /**
     * Struk bukti yang menjadi dasar detail ini (path gambar, kategori, item_code)
     */
    public function strukItem()
    {
        return $this->hasOne(StrukItem::class, 'lpj_detail_id');
    }

    /**
     * Get formatted nominal
     */
    public function getFormattedNominalAttribute()
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    /**
     * Get tipe badge class
     */
    public function getTipeBadgeClass(): string
    {
        return match($this->tipe_penggunaan) {
            'BBM' => 'bg-danger',
            'Tol' => 'bg-primary',
            'Portir' => 'bg-info',
            'Lain-lain' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}
