<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Unit;
use App\Services\ModuleGate;
use App\Services\UnitScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Unit Bisnis';
        $scope = UnitScope::for($request->user());

        $units = Unit::withCount(['sites', 'users'])
            ->with(['sites', 'enabledModules'])
            ->when(! $scope->isGlobal(), fn ($q) => $q->whereKey($request->user()?->unit_id))
            ->orderBy('name')
            ->get();

        $totalModules = Module::active()->count();

        return view('superadmin.units.index', compact('title', 'units', 'totalModules'));
    }

    public function create(Request $request)
    {
        if (! UnitScope::for($request->user())->isGlobal()) {
            abort(403, 'Hanya superadmin Pusat / developer yang dapat menambah unit.');
        }

        $title = 'Tambah Unit Bisnis';

        $modules = Module::active()->ordered()->get();
        $enabledModuleIds = $this->defaultModuleIds($modules);
        $canManageModules = $this->canManageModules(request());

        return view('superadmin.units.form', compact('title', 'modules', 'enabledModuleIds', 'canManageModules'));
    }

    public function store(Request $request)
    {
        if (! UnitScope::for($request->user())->isGlobal()) {
            abort(403, 'Hanya superadmin Pusat / developer yang dapat menambah unit.');
        }

        $validated = $request->validate([
            'code' => 'required|alpha_dash|unique:units,code|max:50',
            'name' => 'required|max:100',
            'description' => 'nullable|max:500',
            'is_active' => 'boolean',
            'modules' => 'nullable|array',
            'modules.*' => 'integer|exists:modules,id',
        ], [
            'code.required' => 'Kode unit harus diisi',
            'code.alpha_dash' => 'Kode unit hanya huruf, angka, dash, underscore',
            'code.unique' => 'Kode unit sudah digunakan',
            'name.required' => 'Nama unit harus diisi',
        ]);

        $unit = Unit::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Checklist modul hanya boleh diatur superadmin unit Pusat. Bagi unit
        // yang dibuat oleh pihak lain, matrix diisi default (BBM + SPD) supaya
        // unit baru tidak pernah lahir tanpa modul apa pun.
        if ($this->canManageModules($request)) {
            $this->syncUnitModules($unit, $this->resolvedModuleIds($request));
        } else {
            $unit->syncDefaultModules();
        }

        return redirect()->route('superadmin.units.index')
            ->with('success', "Unit {$unit->name} berhasil dibuat.");
    }

    public function edit(Request $request, Unit $unit)
    {
        UnitScope::for($request->user())->ensureUnit($unit->id);

        $title = 'Edit Unit Bisnis';
        $unit->loadCount('sites');

        $modules = Module::active()->ordered()->get();

        $enabledModuleIds = $unit->enabledModules()
            ->pluck('modules.id')
            ->all();

        $canManageModules = $this->canManageModules(request());

        return view('superadmin.units.form', compact('title', 'unit', 'modules', 'enabledModuleIds', 'canManageModules'));
    }

    public function update(Request $request, Unit $unit)
    {
        UnitScope::for($request->user())->ensureUnit($unit->id);

        $validated = $request->validate([
            'name' => 'required|max:100',
            'description' => 'nullable|max:500',
            'is_active' => 'boolean',
            'modules' => 'nullable|array',
            'modules.*' => 'integer|exists:modules,id',
        ], [
            'name.required' => 'Nama unit harus diisi',
        ]);

        $unit->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', $unit->is_active),
        ]);

        if ($this->canManageModules($request)) {
            $this->syncUnitModules($unit, $this->resolvedModuleIds($request));
        }

        return redirect()->route('superadmin.units.index')
            ->with('success', "Unit {$unit->name} berhasil diupdate.");
    }

    public function destroy(Request $request, Unit $unit)
    {
        UnitScope::for($request->user())->ensureUnit($unit->id);

        if ($unit->is_central) {
            return redirect()->route('superadmin.units.index')
                ->with('error', "Unit {$unit->name} adalah unit Pusat dan tidak dapat dihapus.");
        }

        $userCount = $unit->users()->count();
        if ($userCount > 0) {
            return redirect()->route('superadmin.units.index')
                ->with('error', "Unit {$unit->name} masih dipakai oleh {$userCount} user. Tidak dapat dihapus.");
        }

        $siteCount = $unit->sites()->count();
        if ($siteCount > 0) {
            return redirect()->route('superadmin.units.index')
                ->with('error', "Unit {$unit->name} masih memiliki {$siteCount} site. Pindahkan site tersebut ke unit lain atau hapus site-nya dulu.");
        }

        $unit->delete();

        return redirect()->route('superadmin.units.index')
            ->with('success', "Unit {$unit->name} berhasil dihapus.");
    }

    /* =========================================================
     * Gate Modul per Unit
     * =======================================================*/

    /**
     * Siapa yang boleh mengubah matrix modul sebuah unit.
     *
     * DUA syarat, keduanya wajib:
     *   1. punya permission `settings.modules`
     *   2. unitnya adalah unit Pusat (is_central)
     *
     * Syarat kedua diperiksa di sini — bukan hanya di route — karena mengubah
     * matrix sebuah unit berarti mengubah hak akses SELURUH user di unit itu.
     * Permission saja tidak cukup: superadmin unit biasa pun tidak boleh
     * menyalakan modul untuk unitnya sendiri.
     *
     * Catatan transisi: user yang belum punya unit (unit_id null) diperlakukan
     * sebagai global, konsisten dengan User::hasGlobalUnitScope().
     */
    private function canManageModules(Request $request): bool
    {
        $actor = $request->user();

        if ($actor === null || ! $actor->hasPermission('settings.modules')) {
            return false;
        }

        return $actor->hasGlobalUnitScope();
    }

    /**
     * Modul yang dicentang pada form.
     *
     * Menangani perilaku checkbox HTML: kalau admin MELEPAS semua centang,
     * field `modules` tidak terkirim sama sekali — sehingga tidak bisa
     * dibedakan dari "bukan submit form". Karena itu form mengirim penanda
     * tersembunyi `modules_present`.
     *
     * - `modules_present` ada  -> hormati checklist apa adanya (boleh kosong)
     * - tidak ada              -> bukan submit form, pakai default (BBM + SPD)
     *
     * @return array<int, int>
     */
    private function resolvedModuleIds(Request $request): array
    {
        if (! $request->boolean('modules_present')) {
            return $this->defaultModuleIds(Module::active()->ordered()->get());
        }

        return collect($request->input('modules', []))
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Modul default untuk unit baru: always-on + default_enabled (BBM & SPD).
     *
     * @param  \Illuminate\Support\Collection<int, Module>  $modules
     * @return array<int, int>
     */
    private function defaultModuleIds($modules): array
    {
        return $modules
            ->filter(fn (Module $m) => $m->is_always_on || $m->default_enabled)
            ->pluck('id')
            ->all();
    }

    /**
     * Terapkan checklist ke matrix unit x modul, lengkap dengan audit.
     *
     * Modul always-on (RBAC & Pengaturan) selalu dipaksa enabled — kalau tidak,
     * Pusat bisa mematikan modul yang dipakai untuk mengelola dirinya sendiri
     * dan terkunci di luar.
     *
     * @param  array<int, int>  $enabledModuleIds
     */
    private function syncUnitModules(Unit $unit, array $enabledModuleIds): void
    {
        $now = now();
        $actorId = auth()->id();
        $logs = [];

        foreach (Module::active()->ordered()->get() as $module) {
            $shouldEnable = $module->is_always_on
                || in_array($module->id, $enabledModuleIds, true);

            $current = DB::table('unit_modules')
                ->where('unit_id', $unit->id)
                ->where('module_id', $module->id)
                ->first();

            if ($current === null) {
                DB::table('unit_modules')->insert([
                    'unit_id' => $unit->id,
                    'module_id' => $module->id,
                    'is_enabled' => $shouldEnable,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } elseif ((bool) $current->is_enabled !== $shouldEnable) {
                DB::table('unit_modules')
                    ->where('id', $current->id)
                    ->update(['is_enabled' => $shouldEnable, 'updated_at' => $now]);
            } else {
                continue;
            }

            $logs[] = [
                'user_id' => $actorId,
                'unit_id' => $unit->id,
                'module_id' => $module->id,
                'action' => $shouldEnable ? 'enable' : 'disable',
                'note' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($logs)) {
            DB::table('module_access_logs')->insert($logs);
        }

        // Matrix berubah -> pastikan peta permission tidak memakai versi lama.
        app(ModuleGate::class)->flush();
    }
}
