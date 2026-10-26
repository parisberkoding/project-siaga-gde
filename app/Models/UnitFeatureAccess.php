<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hak akses fitur sebuah unit, per aksi CRUD.
 *
 * Menyimpan matrix yang dikelola lewat UI "Akses Fitur" (sa/features/on_unit):
 * untuk setiap unit, fitur mana saja yang boleh di-view/create/update/delete.
 */
class UnitFeatureAccess extends Model
{
    protected $table = 'unit_feature_access';

    protected $fillable = [
        'unit_id',
        'feature_id',
        'can_view',
        'can_create',
        'can_update',
        'can_delete',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    /* =========================================================
     * Resolusi hak akses
     * =======================================================*/

    /** Aksi yang didukung. */
    public const ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Apakah matrix ini SUDAH dipakai untuk menegakkan akses di runtime?
     *
     * Saat ini false: matrix hanya menyimpan konfigurasi + menggerakkan UI.
     * Saat false, semua pemanggil hasAccess() mengembalikan true (fail-open),
     * supaya tidak ada yang kehilangan akses sebelum matrix lengkap diisi.
     */
    public static function isWiredIntoAuth(): bool
    {
        return (bool) config('features.enforce_unit_access', false);
    }

    /**
     * Boleh unit ini melakukan aksi tertentu pada sebuah fitur?
     *
     * Fail-open: selama belum di-wire ke runtime, atau bila belum ada baris
     * matrix, akses dianggap boleh — konsisten dengan strategi anti-outage
     * yang dipakai di seluruh fitur.
     */
    public static function unitCan(?int $unitId, ?string $featureCode, string $action): bool
    {
        if (! static::isWiredIntoAuth() || $unitId === null || ! $featureCode) {
            return true;
        }

        $featureId = Feature::where('code', $featureCode)->value('id');

        if (! $featureId) {
            return true; // fitur tidak terdaftar -> tidak dibatasi
        }

        $row = static::where('unit_id', $unitId)->where('feature_id', $featureId)->first();

        if (! $row) {
            return true; // belum dikonfigurasi -> fail-open
        }

        $column = 'can_'.$action;

        return $row->{$column} ?? true;
    }
}
