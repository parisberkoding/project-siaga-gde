<?php

namespace App\Services\AI\DataMart;

use App\Models\User;

/**
 * Lingkup akses data per-user untuk pipeline Data Mart.
 *
 * unitId === null   -> global / pusat (boleh melihat SEMUA unit).
 * unitId int        -> hanya data milik unit user tersebut.
 *
 * siteIds disimpan terpisah untuk module yang masih memakai scope berbasis
 * site (mis. Lunch Management) yang tidak memiliki kolom unit_id langsung.
 *
 * Ditentukan dari User::hasGlobalUnitScope():
 *   - user tanpa unit (unit_id null)                 => global.
 *   - role developers                                => global.
 *   - role superadmin yang unitnya Pusat             => global.
 *   - selain itu (termasuk GA di Pusat)              => dibatasi ke unitnya.
 */
final class DataMartScope
{
    public function __construct(
        public readonly ?int $unitId,
        public readonly ?array $siteIds,
        public readonly string $label,
    ) {}

    public function isGlobal(): bool
    {
        return $this->unitId === null;
    }

    public static function forUser(?User $user): self
    {
        if ($user === null) {
            return new self(null, null, 'pusat');
        }

        if ($user->hasGlobalUnitScope()) {
            return new self(null, null, 'global');
        }

        $unit = $user->unit;

        if ($unit === null) {
            return new self(null, null, 'global');
        }

        return new self($unit->id, $user->unitSiteIds() ?: [], 'unit '.$unit->code);
    }
}
