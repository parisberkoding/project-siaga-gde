<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Unit;
use Illuminate\Support\Facades\Cache;

/**
 * ModuleGate — resolver gate modul per unit.
 *
 * Menjawab satu pertanyaan: "permission apa yang SAH dimiliki seseorang,
 * mengingat modul yang enabled untuk unitnya?"
 *
 * Rumus otorisasi aplikasi:
 *     efektif = punya Permission (RBAC, global) AND modul enabled untuk unit user
 *
 * Ditempatkan di satu service agar hanya ada SATU definisi rumus tersebut.
 * `User::hasPermission()` adalah satu-satunya pemanggil, sehingga seluruh
 * permukaan otorisasi (middleware CheckPermission, Gate, sidebar, blade)
 * otomatis ikut ter-filter tanpa perlu disentuh satu per satu.
 */
class ModuleGate
{
    /**
     * Kunci cache untuk peta permission per modul.
     * Diberi versi supaya invalidasi cukup dengan menaikkan nomor versi,
     * tanpa perlu menyapu kunci satu per satu.
     */
    private const VERSION_KEY = 'module_gate.version';

    private const MAP_KEY = 'module_gate.permission_map';

    /**
     * Peta permission per modul: ['bbm' => ['bbm.view', ...], ...]
     *
     * Data ini sangat kecil (8 modul, ~56 baris pivot) dan jarang berubah,
     * sehingga aman di-cache lama dan hanya di-invalidasi lewat flush().
     *
     * @return array<string, array<int, string>>
     */
    public function permissionMap(): array
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);

        return Cache::rememberForever(self::MAP_KEY.'.v'.$version, function () {
            $map = [];

            foreach (Module::with('permissions')->active()->get() as $module) {
                $map[$module->code] = $module->permissions->pluck('code')->all();
            }

            return $map;
        });
    }

    /**
     * Apakah fitur gate modul sudah diinisialisasi?
     *
     * Kalau tabel `modules` masih kosong, berarti seeder belum dijalankan.
     * Keadaan ini HARUS diperlakukan sebagai "tanpa gating", bukan "tanpa
     * modul" — kalau tidak, deploy yang lupa menjalankan seeder akan mencabut
     * seluruh permission setiap user yang punya unit (outage total).
     */
    public function isInitialized(): bool
    {
        return $this->permissionMap() !== [];
    }

    /**
     * Kode permission yang SAH untuk sekumpulan modul.
     *
     * @param  array<int, string>  $moduleCodes
     * @return array<int, string>
     */
    public function permissionCodesForModules(array $moduleCodes): array
    {
        $map = $this->permissionMap();
        $allowed = [];

        foreach ($moduleCodes as $code) {
            foreach ($map[$code] ?? [] as $permissionCode) {
                $allowed[$permissionCode] = true;
            }
        }

        return array_keys($allowed);
    }

    /**
     * Saring daftar permission milik user berdasarkan modul unitnya.
     *
     * @param  array<int, string>  $permissionCodes  permission mentah dari RBAC
     * @param  array<int, string>|null  $enabledModuleCodes  null = bypass (unit Pusat / tanpa unit)
     * @return array<int, string>
     */
    public function filterPermissionCodes(array $permissionCodes, ?array $enabledModuleCodes): array
    {
        if ($permissionCodes === []) {
            return [];
        }

        // Bypass: unit Pusat, atau user yang belum punya unit sama sekali.
        // Lihat catatan transisi di User::bypassesModuleGate().
        if ($enabledModuleCodes === null) {
            return array_values($permissionCodes);
        }

        $allowed = array_flip($this->permissionCodesForModules($enabledModuleCodes));

        return array_values(array_filter(
            $permissionCodes,
            fn (string $code) => isset($allowed[$code])
        ));
    }

    /**
     * Apakah modul tertentu enabled untuk unit ini?
     * Unit Pusat selalu dianggap true (bypass).
     */
    public function unitHasModule(?Unit $unit, string $moduleCode): bool
    {
        if ($unit === null || $unit->isCentral()) {
            return true;
        }

        return $unit->hasModuleEnabled($moduleCode);
    }

    /**
     * Kosongkan cache peta permission.
     *
     * WAJIB dipanggil setiap kali matrix unit x modul atau pemetaan
     * permission -> modul berubah, kalau tidak perubahan tidak akan terbaca.
     */
    public function flush(): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        Cache::forever(self::VERSION_KEY, $version + 1);
    }
}
