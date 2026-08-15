<?php

namespace App\Services\AI\DataMart;

use App\Models\User;

/**
 * Lingkup akses data per-user untuk pipeline Data Mart.
 *
 * siteIds === null  -> global / pusat (boleh melihat SEMUA unit).
 * siteIds array     -> hanya site milik unit user tersebut.
 *
 * Ditentukan dari relasi User -> Unit:
 *   - user tanpa unit (unit_id null)            => global (pusat).
 *   - user dengan unit PUSAT (Kantor Pusat)     => global (pusat).
 *   - selain itu                                => dibatasi ke site unitnya.
 */
final class DataMartScope
{
    public function __construct(
        public readonly ?array $siteIds,
        public readonly string $label,
    ) {}

    public function isGlobal(): bool
    {
        return $this->siteIds === null;
    }

    public static function forUser(?User $user): self
    {
        if ($user === null) {
            return new self(null, 'pusat');
        }

        $unit = $user->unit;

        if ($unit === null || mb_strtoupper((string) $unit->code) === 'PUSAT') {
            return new self(null, 'pusat');
        }

        return new self($user->unitSiteIds() ?: [], 'unit '.$unit->code);
    }
}
