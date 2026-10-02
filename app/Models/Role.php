<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Role RBAC.
 *
 * Sejak item 4, sebuah role dapat bersifat GLOBAL atau khusus satu unit
 * (lihat migrasi add_unit_id_to_roles_table untuk alasan desainnya):
 *
 *   unit_id = NULL  -> global, berlaku di semua unit
 *   unit_id = <id>  -> hanya untuk unit tersebut
 *
 * Aturan penugasan ditegakkan di Role::isAssignableTo() supaya hanya ada SATU
 * definisi aturan, dan User::assignRole() menjadi satu-satunya jalur penugasan.
 */
class Role extends Model
{
    protected $table = 'roles';

    protected $fillable = [
        'code',
        'name',
        'description',
        'unit_id',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /* =========================================================
     * Relasi
     * =======================================================*/

    /**
     * Relasi ke Permission melalui pivot role_permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * User pemilik role (many-to-many).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Unit pemilik role. Null = role global.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /* =========================================================
     * Segmentasi unit
     * =======================================================*/

    public function isGlobal(): bool
    {
        return $this->unit_id === null;
    }

    /**
     * Role yang berlaku untuk unit tertentu: global + milik unit itu.
     *
     * Unit null (user belum di-assign) hanya mendapat role global. Ini
     * mempertahankan perilaku sekarang — seluruh role yang ada masih global —
     * sehingga backfill unit_id pada user tidak pernah mencabut role siapa pun.
     */
    public function scopeAvailableFor($query, ?int $unitId)
    {
        if ($unitId === null) {
            return $query->whereNull('roles.unit_id');
        }

        return $query->where(function ($q) use ($unitId) {
            $q->whereNull('roles.unit_id')
                ->orWhere('roles.unit_id', $unitId);
        });
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('roles.unit_id');
    }

    /**
     * Boleh role ini diberikan kepada user dengan unit tertentu?
     *
     * - Role global  : selalu boleh (termasuk untuk user ber-unit).
     * - Role ber-unit: hanya bila unit user persis sama.
     */
    public function isAssignableTo(?int $userUnitId): bool
    {
        if ($this->isGlobal()) {
            return true;
        }

        return $userUnitId !== null && (int) $this->unit_id === (int) $userUnitId;
    }

    /**
     * Sync permissions by permission code list.
     */
    public function syncPermissionsByCode(array $codes): void
    {
        $ids = Permission::whereIn('code', $codes)->pluck('id')->toArray();
        $this->permissions()->sync($ids);
    }
}
