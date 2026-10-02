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
 * Ditentukan dari relasi User -> Unit:
 *   - user tanpa unit (unit_id null)            => global (pusat).
 *   - user dengan unit PUSAT (Kantor Pusat)     => global (pusat).
 *   - selain itu                                => dibatasi ke unitnya.
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

        $unit = $user->unit;

        if ($unit === null || mb_strtoupper((string) $unit->code) === 'PUSAT') {
            return new self(null, null, 'pusat');
        }

        return new self($unit->id, $user->unitSiteIds() ?: [], 'unit '.$unit->code);
    }
}
