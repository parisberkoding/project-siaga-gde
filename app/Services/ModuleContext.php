<?php

namespace App\Services;

use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ModuleContext — "modul mana yang sedang dibuka".
 *
 * Latar belakang: sidebar superadmin memuat SELURUH menu dari semua siklus
 * bisnis sekaligus. Setelah modul bertambah, itu menjadi daftar yang panjang
 * dan rawan salah klik. Karena itu alurnya diubah menjadi:
 *
 *      Login -> All Modules -> pilih satu modul -> sidebar & route modul itu
 *
 * Service ini menyimpan pilihan modul di session dan menjadi SATU-SATUNYA
 * tempat yang tahu:
 *   1. modul apa yang boleh dilihat seorang user (RBAC + gate unit),
 *   2. route mana yang menjadi milik modul mana,
 *   3. halaman pendarat (home) untuk tiap modul.
 *
 * Catatan penting: konteks modul bersifat OPSIONAL. Bila session belum punya
 * modul aktif (mis. user lama yang login langsung ke dashboard, atau deep link),
 * aplikasi tetap berjalan seperti sebelumnya. Pengetatan route hanya berlaku
 * setelah user memilih modul dari halaman All Modules.
 */
class ModuleContext
{
    public const SESSION_KEY = 'module_context.active';

    /**
     * Route yang berlaku lintas modul (profil, dashboard, file, auth).
     * Route di sini SELALU boleh diakses dari modul mana pun.
     */
    private const SHARED_ROUTES = [
        'modules.index',
        'modules.activate',
        'profile',
        'profile.*',
        'login',
        'login-process',
        'logout',
        'sso.*',
        'storage.*',
        'welcome',
        'panduan',
        'tentang',
        'up',
        'superadmin-dashboard',
        'superadmin.dashboard.*',
        'superadmin.driver.*',
        'superadmin.pivot.data',
        'dashboard.*',
        'analytics.*',
        'api.datamart.*',
        'admin-dashboard',
        'pelaporan.*',
        'superadmin.dashboard.bisnis',
        'requester.ajax.dashboard.stats',
        'ajax.superadmin.dashboard.stats',
    ];

    /**
     * Peta route -> kode modul.
     *
     * Ditulis eksplisit (bukan diturunkan dari prefix permission) karena
     * permission `pelaporan.*` dan `dashboard.*` dipakai bersama beberapa
     * modul. Urutan tidak berpengaruh karena kode modul dicari per pola dan
     * setiap route hanya boleh muncul di satu modul.
     *
     * Route yang tidak terdaftar di sini diperlakukan sebagai netral
     * (fail-open) supaya penambahan route baru tidak langsung mengunci user.
     *
     * @var array<string, array<int, string>>
     */
    private const ROUTE_MODULES = [
        'bbm' => [
            'modules.dashboard.bbm',
            'requester-index',
            'requester-create',
            'requester-cancel',
            'requester.cancel-request',
            'requester-history',
            'requester-show',
            'requester-upload-proof',
            'requester.submit-report',
            'admin-approval',
            'admin-process',
            'admin-bulk-process',
            'transaction.getPhoto',
            'admin.vouchers.*',
            'voucher.*',
            'superadmin.permohonan*',
            'superadmin.laporan.*',
            'superadmin.print-report*',
        ],

        'spd' => [
            'modules.dashboard.spd',
            'requester.surat.*',
            'requester.spd.berjalan',
            'requester.lpj.*',
            'requester.perpanjangan.*',
            'requester.ajax.surat.*',
            'spd.*',
            'surat.superadmin.*',
            'perpanjangan.superadmin.*',
            'lpj.*',
            'lpj.superadmin.*',
            'lpj.pengguna.*',
            'lpj.management.*',
            'ajax.superadmin.*',
        ],

        'vehicle' => [
            'modules.dashboard.vehicle',
            'vehicles',
            'vehicles.*',
            'vehicle.*',
            'superadmin.vehicle.*',
            'superadmin.vehicle-activation*',
            'superadmin.barcode.*',
            'vehicle-checking.*',
            'superadmin.vehicle-health.*',
            'superadmin.financial-report.*',
            'superadmin.transaction-report.*',
            'superadmin.trip-analysis.*',
            'superadmin.trip-validations.*',
            'trip-validations.*',
            'dispatcher.*',
            'admin.replacement-approval.*',
            'admin.service-scheduling.*',
            'admin.transaction-monitoring.*',
            'admin.sparepart-request.*',
            'requester.checking-acknowledgement.*',
            'requester.replacement-schedule.*',
            'requester.service-transaction.*',
        ],

        'lunch' => [
            'lunch.*',
            'lunch-attendance.*',
            'my.lunch-plan*',
            'vendor.lunch.*',
        ],

        'ai' => [
            'modules.dashboard.ai',
            'ai-assistant.*',
        ],

        'users' => [
            'modules.dashboard.users',
            'users',
            'users.*',
        ],

        'rbac' => [
            'modules.dashboard.rbac',
            'superadmin.rbac.*',
        ],

        'settings' => [
            'modules.dashboard.settings',
            'master-gasoline.*',
            'superadmin.units.*',
            'superadmin.sites.*',
            'superadmin.sso.*',
            'superadmin.features.*',
        ],
    ];

    /**
     * Dashboard tiap modul. Ini menjadi halaman pendarat utama modul.
     * Modul yang belum punya dashboard khusus akan jatuh ke HOME_ROUTES.
     *
     * @var array<string, string>
     */
    private const DASHBOARD_ROUTES = [
        'bbm' => 'modules.dashboard.bbm',
        'spd' => 'modules.dashboard.spd',
        'vehicle' => 'modules.dashboard.vehicle',
        'lunch' => 'lunch.dashboard',
        'ai' => 'modules.dashboard.ai',
        'users' => 'modules.dashboard.users',
        'rbac' => 'modules.dashboard.rbac',
        'settings' => 'modules.dashboard.settings',
    ];

    /**
     * Halaman pertama tiap modul, diurutkan dari hak paling kuat.
     * Entry yang permission-nya tidak dimiliki user akan dilewati.
     *
     * @var array<string, array<int, array{0: string, 1: string}>>
     */
    private const HOME_ROUTES = [
        'bbm' => [
            ['bbm.approve', 'superadmin.permohonan'],
            ['bbm.request', 'requester-index'],
            ['bbm.view', 'requester-history'],
            ['bbm.export', 'superadmin.print-report'],
        ],
        'spd' => [
            ['spd.manage', 'surat.superadmin.index'],
            ['spd.create', 'requester.surat.create'],
            ['spd.validate_trip', 'lpj.pengguna.index'],
            ['lpj.validate', 'lpj.superadmin.index'],
            ['spd.view', 'requester.surat.index'],
        ],
        'vehicle' => [
            ['vehicle.view', 'vehicles'],
            ['vehicle.checking', 'dispatcher.daily-check.index'],
            ['vehicle.report', 'superadmin.vehicle-health.index'],
            ['vehicle.sparepart', 'admin.replacement-approval.index'],
            ['vehicle.acknowledge', 'requester.checking-acknowledgement.index'],
            ['vehicle.export', 'dispatcher.export-checking.index'],
        ],
        'lunch' => [
            ['lunch.view', 'lunch.dashboard'],
            ['lunch.manage', 'lunch.employee-defaults.index'],
        ],
        'ai' => [
            ['ai.view', 'ai-assistant.index'],
        ],
        'users' => [
            ['users.view', 'users'],
        ],
        'rbac' => [
            ['rbac.roles', 'superadmin.rbac.index'],
        ],
        'settings' => [
            ['settings.manage', 'superadmin.units.index'],
            ['features.manage', 'superadmin.features.index'],
            ['settings.view', 'master-gasoline.index'],
        ],
    ];

    private ?Module $currentModule = null;

    private bool $resolved = false;

    /* =========================================================
     * Konteks aktif
     * =======================================================*/

    /**
     * Modul yang sedang aktif di session, atau null bila user berada di
     * halaman lintas modul (All Modules, profil, dashboard).
     */
    public function current(): ?Module
    {
        if ($this->resolved) {
            return $this->currentModule;
        }

        $this->resolved = true;

        $code = session(self::SESSION_KEY);

        if (! is_string($code) || $code === '') {
            return null;
        }

        $module = Module::query()->active()->where('code', $code)->first();

        if (! $module) {
            $this->forget();

            return null;
        }

        // Modul bisa saja dimatikan Pusat saat user masih membuka modulnya.
        $user = $this->user();

        if ($user && ! $this->userCanAccess($user, $module)) {
            $this->forget();

            return null;
        }

        return $this->currentModule = $module;
    }

    /**
     * Simpan pilihan modul ke session.
     */
    public function set(Module $module): void
    {
        session([self::SESSION_KEY => $module->code]);

        $this->resolved = true;
        $this->currentModule = $module;
    }

    /**
     * Keluar dari konteks modul (kembali ke All Modules).
     */
    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);

        $this->resolved = true;
        $this->currentModule = null;
    }

    /* =========================================================
     * Modul yang tersedia untuk user
     * =======================================================*/

    /**
     * Modul aktif yang boleh dibuka user ini.
     *
     * Dua saringan sekaligus:
     *   - gate modul per unit (ModuleGate / User::hasModuleAccess)
     *   - user punya minimal satu permission milik modul tersebut
     *
     * Superadmin selalu melihat semua modul aktif (unit Pusat memang bypass
     * gate, dan role superadmin memegang seluruh permission).
     *
     * @return Collection<int, Module>
     */
    public function availableModules(User $user): Collection
    {
        $permissionCodes = $user->getPermissionCodes();
        $isSuperadmin = $this->isSuperadmin($user);

        return Module::query()
            ->active()
            ->ordered()
            ->with('permissions')
            ->get()
            ->filter(function (Module $module) use ($user, $permissionCodes, $isSuperadmin) {
                if (! $user->hasModuleAccess($module->code)) {
                    return false;
                }

                if ($isSuperadmin) {
                    return true;
                }

                $modulePermissions = $module->permissions->pluck('code')->all();

                // `dashboard.view` dipetakan ke banyak modul agar dashboard
                // tetap hidup selama minimal satu siklus aktif. Permission itu
                // TIDAK boleh membuat modul dianggap bisa dibuka — kalau iya,
                // user yang hanya boleh melihat dashboard akan melihat semua
                // kartu modul.
                $modulePermissions = array_values(array_diff($modulePermissions, ['dashboard.view']));

                return count(array_intersect($modulePermissions, $permissionCodes)) > 0;
            })
            ->values();
    }

    /**
     * Apakah user boleh membuka modul ini?
     */
    public function userCanAccess(User $user, Module $module): bool
    {
        return $this->availableModules($user)->contains(
            fn (Module $candidate) => $candidate->is($module)
        );
    }

    public function isSuperadmin(User $user): bool
    {
        if ($user->role === 'superadmin') {
            return true;
        }

        return $user->roles()->where('code', 'superadmin')->exists();
    }

    /* =========================================================
     * Route -> modul
     * =======================================================*/

    /**
     * Kode modul pemilik sebuah route, atau null bila route lintas modul /
     * tidak terdaftar.
     */
    public function moduleForRoute(?string $routeName): ?string
    {
        if (! $routeName || $this->isSharedRoute($routeName)) {
            return null;
        }

        foreach (self::ROUTE_MODULES as $moduleCode => $patterns) {
            if (Str::is($patterns, $routeName)) {
                return $moduleCode;
            }
        }

        return null;
    }

    public function isSharedRoute(string $routeName): bool
    {
        return Str::is(self::SHARED_ROUTES, $routeName);
    }

    public function moduleName(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        return Module::query()->where('code', $code)->value('name');
    }

    /* =========================================================
     * Halaman pendarat modul
     * =======================================================*/

    /**
     * Route pertama yang masuk akal untuk sebuah modul, berdasarkan
     * permission user. Null bila tidak ada yang cocok.
     */
    public function routeForModule(Module $module, User $user): ?string
    {
        // Dashboard modul menjadi halaman pendarat utama. Pemanggil sudah
        // memastikan user punya akses modul (userCanAccess), jadi tidak perlu
        // menebak permission lagi.
        if (isset(self::DASHBOARD_ROUTES[$module->code])) {
            return route(self::DASHBOARD_ROUTES[$module->code]);
        }

        $permissionCodes = $user->getPermissionCodes();

        foreach (self::HOME_ROUTES[$module->code] ?? [] as [$permission, $routeName]) {
            if (in_array($permission, $permissionCodes, true)) {
                return route($routeName);
            }
        }

        return null;
    }

    private function user(): ?User
    {
        return Auth::user() ?? request()->user();
    }
}
