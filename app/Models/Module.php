<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modul = satu SIKLUS BISNIS utuh (input -> proses -> report -> dashboard).
 *
 * Ini konsep yang BERBEDA dari PermissionGroup:
 *   PermissionGroup  -> pengelompokan UI di halaman RBAC
 *   Module           -> satuan yang di-enable/disable per unit
 *
 * Keduanya sengaja dipisah (keputusan desain) meskipun isinya sebagian besar
 * mirip, karena `pelaporan` menaungi dua siklus sekaligus sehingga tidak bisa
 * dipetakan satu-lawan-satu dengan PermissionGroup.
 */
class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'order',
        'is_gateable',
        'is_always_on',
        'default_enabled',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_gateable' => 'boolean',
        'is_always_on' => 'boolean',
        'default_enabled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /* =========================================================
     * Relasi
     * =======================================================*/

    /**
     * Permission yang menjadi milik modul ini (many-to-many).
     * Satu permission boleh dimiliki lebih dari satu modul.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'module_permissions',
            'module_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Unit yang memiliki baris matrix untuk modul ini.
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(
            Unit::class,
            'unit_modules',
            'module_id',
            'unit_id'
        )->withPivot('is_enabled')->withTimestamps();
    }

    /* =========================================================
     * Scope
     * =======================================================*/

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Modul yang boleh dimatikan per unit (semua kecuali RBAC & Pengaturan).
     */
    public function scopeGateable($query)
    {
        return $query->where('is_gateable', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /* =========================================================
     * Helper
     * =======================================================*/

    /**
     * Kumpulan kode permission milik modul ini.
     * Dipakai untuk memfilter permission user berdasarkan modul yang enabled.
     *
     * @return array<int, string>
     */
    public function permissionCodes(): array
    {
        return $this->permissions()->pluck('code')->all();
    }

    /**
     * Modul yang aktif secara default untuk unit baru (mis. BBM + SPD).
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function defaultEnabled()
    {
        return static::query()
            ->active()
            ->where(fn ($q) => $q->where('default_enabled', true)->orWhere('is_always_on', true))
            ->ordered()
            ->get();
    }

    /**
     * Apakah modul ini selalu aktif dan karenanya tidak boleh dimatikan per unit.
     */
    public function isAlwaysOn(): bool
    {
        return $this->is_always_on;
    }
}
