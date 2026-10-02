<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Unit extends Model
{
    use HasFactory;

    /**
     * `is_central` SENGAJA tidak masuk fillable.
     *
     * Kolom ini menentukan bypass gate modul DAN bypass data scoping, jadi
     * hanya boleh diubah lewat jalur eksplisit oleh superadmin unit Pusat —
     * bukan lewat mass-assignment dari form.
     */
    protected $fillable = [
        'code',
        'name',
        'province',
        'city',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_central' => 'boolean',
    ];

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /* =========================================================
     * Gate Modul per Unit
     * =======================================================*/

    /**
     * Semua baris matrix modul untuk unit ini (termasuk yang disabled).
     * Dipakai oleh UI matrix supaya modul yang dimatikan tetap tampil.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'unit_modules',
            'unit_id',
            'module_id'
        )->withPivot('is_enabled')->withTimestamps();
    }

    /**
     * Hanya modul yang enabled untuk unit ini.
     */
    public function enabledModules(): BelongsToMany
    {
        return $this->modules()->wherePivot('is_enabled', true);
    }

    /**
     * Kode modul yang enabled untuk unit ini.
     *
     * @return array<int, string>
     */
    public function enabledModuleCodes(): array
    {
        return $this->enabledModules()->pluck('modules.code')->all();
    }

    public function hasModuleEnabled(string $moduleCode): bool
    {
        return in_array($moduleCode, $this->enabledModuleCodes(), true);
    }

    /**
     * True bila unit ini adalah unit Pusat (bypass gate modul & data scoping).
     */
    public function isCentral(): bool
    {
        return (bool) $this->is_central;
    }

    /**
     * Isi matrix modul untuk unit BARU.
     *
     * Memakai `default_enabled` (mis. BBM + SPD), ditambah modul always-on
     * (RBAC & Pengaturan). Idempotent, sehingga aman dipanggil ulang.
     */
    public function syncDefaultModules(): void
    {
        $now = now();
        $existing = DB::table('unit_modules')
            ->where('unit_id', $this->id)
            ->pluck('module_id')
            ->all();

        $rows = Module::query()
            ->active()
            ->ordered()
            ->get()
            ->reject(fn (Module $m) => in_array($m->id, $existing, true))
            ->map(fn (Module $m) => [
                'unit_id' => $this->id,
                'module_id' => $m->id,
                'is_enabled' => $m->is_always_on || $m->default_enabled,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if (! empty($rows)) {
            DB::table('unit_modules')->insert($rows);
        }
    }
}
