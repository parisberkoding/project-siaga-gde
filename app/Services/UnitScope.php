<?php

namespace App\Services;

use App\Models\EmployeeDefault;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * UnitScope — satu pintu untuk batas data berbasis Unit.
 *
 * Aturan (lihat User::hasGlobalUnitScope()):
 *   - user tanpa unit (transisi)              -> global;
 *   - role developers                         -> global;
 *   - role superadmin yang unitnya Pusat      -> global;
 *   - selain itu                              -> hanya site di unitnya.
 *
 * Pemakaian di controller:
 *   $scope = UnitScope::for($request->user());
 *   $query->inUnit($scope->siteIds());
 *   $scope->ensureSite($site);
 *
 * `siteIds() === null` berarti TIDAK dibatasi. `[]` berarti tidak punya site
 * sama sekali (fail-closed), sehingga query otomatis kosong.
 */
final class UnitScope
{
    public function __construct(private readonly ?User $user)
    {
    }

    public static function for(?User $user): self
    {
        return new self($user);
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function isGlobal(): bool
    {
        if ($this->user === null) {
            return true;
        }

        return $this->user->hasGlobalUnitScope();
    }

    public function isScoped(): bool
    {
        return ! $this->isGlobal();
    }

    /**
     * Unit ID untuk scope query berbasis unit_id.
     * null = global (jangan dibatasi).
     */
    public function unitId(): ?int
    {
        return $this->isGlobal() ? null : $this->user?->unit_id;
    }

    /**
     * @return array<int, int>|null null = global
     */
    public function siteIds(): ?array
    {
        if ($this->user === null) {
            return null;
        }

        return $this->user->unitSiteIds();
    }

    /**
     * Unit yang boleh dilihat.
     *
     * @return Collection<int, Unit>
     */
    public function units(): Collection
    {
        if ($this->isGlobal()) {
            return Unit::active()->orderBy('name')->get();
        }

        return Unit::query()
            ->whereKey($this->user?->unit_id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Site yang boleh dilihat.
     *
     * @return Collection<int, Site>
     */
    public function sites(): Collection
    {
        return $this->scopeSites(Site::active()->with('unit'))->orderBy('name')->get();
    }

    public function allowsUnit(?int $unitId): bool
    {
        if ($this->isGlobal()) {
            return true;
        }

        if ($unitId === null) {
            return false;
        }

        return (int) $this->user?->unit_id === (int) $unitId;
    }

    public function allowsSite(Site|int|null $site): bool
    {
        if ($this->isGlobal()) {
            return true;
        }

        if ($site === null) {
            return false;
        }

        $site = $site instanceof Site ? $site : Site::find($site);

        if (! $site) {
            return false;
        }

        $siteIds = $this->siteIds();

        return $siteIds !== null && in_array((int) $site->id, array_map('intval', $siteIds), true);
    }

    public function allowsEmployee(EmployeeDefault $employee): bool
    {
        if ($this->isGlobal()) {
            return true;
        }

        return $this->allowsUnit($employee->resolvedUnitId());
    }

    public function ensureSite(Site|int|null $site): void
    {
        if (! $this->allowsSite($site)) {
            abort(403, 'Site ini berada di luar unit Anda.');
        }
    }

    public function ensureUnit(?int $unitId): void
    {
        if (! $this->allowsUnit($unitId)) {
            abort(403, 'Unit ini berada di luar kewenangan Anda.');
        }
    }

    public function ensureEmployee(EmployeeDefault $employee): void
    {
        if (! $this->allowsEmployee($employee)) {
            abort(403, 'Karyawan ini berada di luar unit Anda.');
        }
    }

    /**
     * Terapkan batas site ke query yang memiliki kolom `site_id`.
     */
    public function scopeBySiteColumn(Builder $query, string $column = 'site_id'): Builder
    {
        $siteIds = $this->siteIds();

        if ($siteIds === null) {
            return $query;
        }

        return $query->whereIn($column, $siteIds);
    }

    /**
     * Terapkan batas ke query Site (kolom id).
     */
    public function scopeSites(Builder $query): Builder
    {
        $siteIds = $this->siteIds();

        if ($siteIds === null) {
            return $query;
        }

        return $query->whereIn('id', $siteIds);
    }
}
