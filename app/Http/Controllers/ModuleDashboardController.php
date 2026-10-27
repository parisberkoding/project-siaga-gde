<?php

namespace App\Http\Controllers;

use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Feature;
use App\Models\LpjPenggantian;
use App\Models\MasterGasoline;
use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\Site;
use App\Models\SsoRoleMapping;
use App\Models\SuratSpd;
use App\Models\SuratSpdExtension;
use App\Models\Unit;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use App\Models\VehicleCheckingDetail;
use App\Models\VehicleCheckingSchedule;
use App\Models\VehiclePartsReplacement;
use App\Services\ModuleContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dashboard per modul.
 *
 * Dashboard BBM memakai halaman analitik BBM yang sudah ada
 * (SuperadminController::dashboardBisnis). Dashboard modul lain dibangun di
 * sini dengan prinsip yang sama:
 *   - angka utama modul (KPI),
 *   - tren 6 bulan,
 *   - antrean kerja yang butuh tindakan,
 *   - pintasan ke aksi utama.
 *
 * Semua query menghormati unit bisnis user: unit non-Pusat hanya melihat
 * datanya sendiri, Pusat/tanpa unit melihat seluruh data.
 */
class ModuleDashboardController extends Controller
{
    /**
     * Unit yang dipakai untuk scoping query.
     * null = tanpa scope (unit Pusat / user belum punya unit).
     */
    private function scopeUnitId(Request $request): ?int
    {
        $user = $request->user();

        return $user->hasUnitScope() ? $user->unit_id : null;
    }

    /**
     * Driver tidak memakai dashboard analitik modul.
     *
     * Halaman dashboard modul memuat agregat seluruh unit (statistik per
     * driver, pivot, tren) — itu alat pantau superadmin/GA/manager. Driver
     * dialihkan ke dashboard driver yang hanya berisi pekerjaan pribadinya.
     */
    private function redirectDriver(Request $request): ?RedirectResponse
    {
        if (! app(ModuleContext::class)->isDriver($request->user())) {
            return null;
        }

        return redirect()->route('driver.dashboard');
    }

    private function module(string $code, Request $request): Module
    {
        return Module::query()->where('code', $code)->firstOrFail();
    }

    /**
     * Tren jumlah per bulan (6 bulan terakhir) dari sekumpulan baris.
     *
     * @param  Collection<int, object>  $rows
     * @param  callable(object): ?Carbon  $dateOf
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function monthlyTrend($rows, callable $dateOf, int $months = 6): array
    {
        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');

            $labels[] = $month->translatedFormat('M');
            $values[] = $rows->filter(function ($row) use ($dateOf, $key) {
                $date = $dateOf($row);

                return $date && $date->format('Y-m') === $key;
            })->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Distribusi status dengan label & warna untuk view.
     *
     * @param  array<string, int>  $counts
     * @param  array<string, string>  $labels
     * @return array<int, array{key: string, label: string, total: int}>
     */
    private function statusBreakdown(array $counts, array $labels): array
    {
        $rows = [];

        foreach ($labels as $key => $label) {
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'total' => (int) ($counts[$key] ?? 0),
            ];
        }

        return $rows;
    }

    /* =========================================================
     * SPD & LPJ
     * =======================================================*/

    public function spd(Request $request): View|RedirectResponse
    {
        // Driver punya dashboard sendiri: jangan tampilkan agregat unit.
        if ($redirect = $this->redirectDriver($request)) {
            return $redirect;
        }

        $module = $this->module('spd', $request);
        $unitId = $this->scopeUnitId($request);

        $suratBase = SuratSpd::query()->inUnit($unitId);
        $lpjBase = LpjPenggantian::query()->inUnit($unitId);
        $extensionBase = SuratSpdExtension::query()->inUnit($unitId);

        $suratStatus = (clone $suratBase)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $lpjStatus = (clone $lpjBase)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $thisMonth = now()->startOfMonth();

        $suratBulanIni = (clone $suratBase)->where('created_at', '>=', $thisMonth)->count();
        $menungguValidasi = (int) ($suratStatus['pending'] ?? 0);
        $perjalananBerjalan = (clone $suratBase)
            ->whereIn('status', ['approved', 'completed'])
            ->whereDate('tanggal_berangkat', '<=', today())
            ->whereDate('tanggal_kembali', '>=', today())
            ->count();
        $lpjMenunggu = (int) ($lpjStatus['pending'] ?? 0);
        $lpjTerlambat = (clone $lpjBase)->where('is_late', true)->count();
        $perpanjanganMenunggu = (clone $extensionBase)->where('status', 'pending')->count();

        $totalBudget = (clone $suratBase)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('total_budget');

        $suratTerbaru = (clone $suratBase)
            ->with(['user', 'vehicle'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        $lpjPerluPerhatian = (clone $lpjBase)
            ->with(['suratSpd.user'])
            ->where(fn ($query) => $query->where('status', 'pending')->orWhere('is_late', true))
            ->latest('created_at')
            ->limit(6)
            ->get();

        $perjalananAktif = (clone $suratBase)
            ->with(['user', 'vehicle'])
            ->whereIn('status', ['approved', 'completed'])
            ->whereDate('tanggal_berangkat', '<=', today())
            ->whereDate('tanggal_kembali', '>=', today())
            ->orderBy('tanggal_kembali')
            ->limit(6)
            ->get();

        $trendRows = (clone $suratBase)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['created_at']);

        $trend = $this->monthlyTrend($trendRows, fn ($row) => $row->created_at);

        $statusBars = $this->statusBreakdown($suratStatus, [
            'pending' => 'Menunggu Validasi',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
        ]);

        return view('modules.dashboard.spd', compact(
            'module', 'suratBulanIni', 'menungguValidasi', 'perjalananBerjalan',
            'lpjMenunggu', 'lpjTerlambat', 'perpanjanganMenunggu', 'totalBudget',
            'suratTerbaru', 'lpjPerluPerhatian', 'perjalananAktif', 'trend', 'statusBars'
        ));
    }

    /* =========================================================
     * Kendaraan & Alat
     * =======================================================*/

    public function vehicle(Request $request): View|RedirectResponse
    {
        // Driver punya dashboard sendiri: jangan tampilkan agregat unit.
        if ($redirect = $this->redirectDriver($request)) {
            return $redirect;
        }

        $module = $this->module('vehicle', $request);
        $unitId = $this->scopeUnitId($request);

        $vehicleBase = VehicleAndToolsConsumers::query()->inUnit($unitId);
        $vprBase = VehiclePartsReplacement::query()->inUnit($unitId);
        $scheduleBase = VehicleCheckingSchedule::query()->inUnit($unitId)->where('is_active', true);

        $totalKendaraan = (clone $vehicleBase)->count();
        $kendaraanAktif = (clone $vehicleBase)->where('is_active', true)->count();

        $vprStatus = (clone $vprBase)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $vprMenunggu = (int) ($vprStatus['pending'] ?? 0);
        $vprBerjalan = (int) (($vprStatus['approved'] ?? 0) + ($vprStatus['scheduled'] ?? 0) + ($vprStatus['in_service'] ?? 0));

        $checkBase = VehicleCheckingDetail::query()
            ->when($unitId, fn ($query) => $query->whereHas(
                'vehicleChecking.vehicle',
                fn ($vehicle) => $vehicle->where('unit_id', $unitId)
            ));

        $cekHariIni = (clone $checkBase)->whereDate('checked_at', today())->count();
        $cekBulanIni = (clone $checkBase)->where('checked_at', '>=', now()->startOfMonth())->count();

        $temuanNg = (clone $checkBase)
            ->whereHas('itemResults', fn ($query) => $query->where('status', 'ng'))
            ->where('checked_at', '>=', now()->startOfMonth())
            ->count();

        $jadwalOverdue = (clone $scheduleBase)
            ->where(fn ($query) => $query->where('status', 'overdue')->orWhereDate('next_due_date', '<', today()))
            ->count();

        $jadwalHariIni = (clone $scheduleBase)->whereDate('next_due_date', today())->count();

        $jadwalTerdekat = (clone $scheduleBase)
            ->with('vehicle')
            ->whereDate('next_due_date', '<=', today()->addDays(7))
            ->orderBy('next_due_date')
            ->limit(6)
            ->get();

        $vprTerbaru = (clone $vprBase)
            ->with('vehicle')
            ->latest('created_at')
            ->limit(6)
            ->get();

        $trendRows = (clone $checkBase)
            ->where('checked_at', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['checked_at']);

        $trend = $this->monthlyTrend($trendRows, fn ($row) => $row->checked_at);

        $statusBars = $this->statusBreakdown($vprStatus, [
            'pending' => 'Menunggu Approval',
            'approved' => 'Disetujui',
            'scheduled' => 'Dijadwalkan',
            'in_service' => 'Dalam Service',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
        ]);

        return view('modules.dashboard.vehicle', compact(
            'module', 'totalKendaraan', 'kendaraanAktif', 'vprMenunggu', 'vprBerjalan',
            'cekHariIni', 'cekBulanIni', 'temuanNg', 'jadwalOverdue', 'jadwalHariIni',
            'jadwalTerdekat', 'vprTerbaru', 'trend', 'statusBars'
        ));
    }

    /* =========================================================
     * AI Assistant
     * =======================================================*/

    public function ai(Request $request): View
    {
        $module = $this->module('ai', $request);
        $user = $request->user();
        $isSuperadmin = app(ModuleContext::class)->isSuperadmin($user);

        $sessionBase = AiChatSession::query()
            ->when(! $isSuperadmin, fn ($query) => $query->where('user_id', $user->id));

        $totalSesi = (clone $sessionBase)->count();
        $sesiBulanIni = (clone $sessionBase)->where('created_at', '>=', now()->startOfMonth())->count();
        $penggunaAktif = (clone $sessionBase)->distinct()->count('user_id');

        $totalPesan = AiChatMessage::query()
            ->whereHas('session', function ($query) use ($isSuperadmin, $user) {
                if (! $isSuperadmin) {
                    $query->where('user_id', $user->id);
                }
            })
            ->count();

        $sesiTerbaru = (clone $sessionBase)
            ->with('user')
            ->withCount('messages')
            ->latest('updated_at')
            ->limit(8)
            ->get();

        return view('modules.dashboard.ai', compact(
            'module', 'totalSesi', 'sesiBulanIni', 'penggunaAktif', 'totalPesan', 'sesiTerbaru'
        ));
    }

    /* =========================================================
     * Manajemen Pengguna
     * =======================================================*/

    public function users(Request $request): View
    {
        $module = $this->module('users', $request);
        $actor = $request->user();
        $unitId = $this->scopeUnitId($request);

        $userBase = User::query()->when($unitId, fn ($query) => $query->where('unit_id', $unitId));

        $totalUser = (clone $userBase)->count();
        $userAktif = (clone $userBase)->where('is_active', true)->count();
        $userNonaktif = $totalUser - $userAktif;
        $userBaru = (clone $userBase)->where('created_at', '>=', now()->startOfMonth())->count();

        $roleCounts = Role::query()
            ->withCount(['users' => function ($query) use ($unitId) {
                if ($unitId) {
                    $query->where('unit_id', $unitId);
                }
            }])
            ->orderByDesc('users_count')
            ->get();

        $unitCounts = Unit::query()
            ->when($unitId, fn ($query) => $query->where('id', $unitId))
            ->withCount(['users' => function ($query) use ($unitId) {
                if ($unitId) {
                    $query->where('unit_id', $unitId);
                }
            }])
            ->orderByDesc('users_count')
            ->get();

        $userTerbaru = (clone $userBase)
            ->with(['unit', 'roles'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('modules.dashboard.users', compact(
            'module', 'totalUser', 'userAktif', 'userNonaktif', 'userBaru',
            'roleCounts', 'unitCounts', 'userTerbaru', 'unitId'
        ));
    }

    /* =========================================================
     * RBAC & Hak Akses
     * =======================================================*/

    public function rbac(Request $request): View
    {
        $module = $this->module('rbac', $request);

        $totalRole = Role::count();
        $totalPermission = Permission::count();
        $totalGroup = PermissionGroup::count();
        $userTanpaRole = User::query()->whereDoesntHave('roles')->count();

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderByDesc('users_count')
            ->orderBy('name')
            ->get();

        $roleTerbaru = Role::query()
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('modules.dashboard.rbac', compact(
            'module', 'totalRole', 'totalPermission', 'totalGroup', 'userTanpaRole',
            'roles', 'roleTerbaru'
        ));
    }

    /* =========================================================
     * Pengaturan & Data Master
     * =======================================================*/

    public function settings(Request $request): View
    {
        $module = $this->module('settings', $request);

        $totalUnit = Unit::count();
        $totalSite = Site::count();
        $totalModul = Module::where('is_active', true)->count();
        $fiturDevelopment = Feature::where('stage', Feature::STAGE_DEVELOPMENT)->count();
        $fiturProduction = Feature::where('stage', Feature::STAGE_PRODUCTION)->count();
        $totalSsoMapping = SsoRoleMapping::where('is_active', true)->count();
        $totalHargaBbm = MasterGasoline::count();
        $hargaTerakhir = MasterGasoline::query()->latest('updated_at')->first();

        $modules = Module::query()
            ->withCount([
                'permissions',
                'units' => fn ($query) => $query->where('unit_modules.is_enabled', true),
            ])
            ->ordered()
            ->get();

        $units = Unit::query()
            ->withCount(['sites', 'users'])
            ->orderBy('name')
            ->get();

        return view('modules.dashboard.settings', compact(
            'module', 'totalUnit', 'totalSite', 'totalModul', 'fiturDevelopment',
            'fiturProduction', 'totalSsoMapping', 'totalHargaBbm', 'hargaTerakhir',
            'modules', 'units'
        ));
    }
}
