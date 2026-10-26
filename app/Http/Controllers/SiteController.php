<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Unit;
use App\Services\UnitScope;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Site / Lokasi';
        $scope = UnitScope::for($request->user());

        $search = $request->get('search');
        $unitId = $request->get('unit_id');

        $query = Site::with('unit')
            ->withCount(['vehicleDetails', 'users', 'employeeDefaults'])
            ->when(! $scope->isGlobal(), fn ($q) => $q->where('unit_id', $request->user()?->unit_id))
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($unitId) {
            $scope->ensureUnit((int) $unitId);
            $query->where('unit_id', $unitId);
        }

        $sites = $query->paginate(20);
        $units = $scope->units();

        return view('superadmin.sites.index', compact('title', 'sites', 'units', 'search', 'unitId'));
    }

    public function create(Request $request)
    {
        $title = 'Tambah Site';
        $scope = UnitScope::for($request->user());
        $units = $scope->units();

        return view('superadmin.sites.form', compact('title', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|alpha_dash|unique:sites,code|max:20',
            'name' => 'required|max:100',
            'type' => 'nullable|in:office,power_plant,basecamp,reservoir,well,other',
            'unit_id' => 'required|exists:units,id',
            'province_slug' => 'nullable|max:100',
            'is_active' => 'boolean',
        ], [
            'code.required' => 'Kode site harus diisi',
            'code.alpha_dash' => 'Kode site hanya huruf, angka, dash, underscore',
            'code.unique' => 'Kode site sudah digunakan',
            'name.required' => 'Nama site harus diisi',
            'type.required' => 'Tipe site harus dipilih',
            'unit_id.required' => 'Site harus terhubung ke unit',
        ]);

        $scope = UnitScope::for($request->user());
        $unitId = $scope->isGlobal()
            ? $validated['unit_id']
            : $request->user()?->unit_id;

        $scope->ensureUnit((int) $unitId);

        Site::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'type' => $validated['type'] ?? \App\Models\Site::TYPE_OTHER,
            'unit_id' => $unitId,
            'province_slug' => $validated['province_slug'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('superadmin.sites.index')
            ->with('success', 'Site ' . strtoupper($validated['code']) . ' berhasil dibuat.');
    }

    public function edit(Request $request, Site $site)
    {
        $scope = UnitScope::for($request->user());
        $scope->ensureSite($site);

        $title = 'Edit Site';
        $units = $scope->units();

        return view('superadmin.sites.form', compact('title', 'site', 'units'));
    }

    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'type' => 'nullable|in:office,power_plant,basecamp,reservoir,well,other',
            'unit_id' => 'required|exists:units,id',
            'province_slug' => 'nullable|max:100',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama site harus diisi',
            'type.required' => 'Tipe site harus dipilih',
            'unit_id.required' => 'Site harus terhubung ke unit',
        ]);

        $scope = UnitScope::for($request->user());
        $scope->ensureSite($site);

        $unitId = $scope->isGlobal()
            ? $validated['unit_id']
            : $request->user()?->unit_id;

        $scope->ensureUnit((int) $unitId);

        $site->update([
            'name' => $validated['name'],
            'type' => $validated['type'] ?? \App\Models\Site::TYPE_OTHER,
            'unit_id' => $unitId,
            'province_slug' => $validated['province_slug'] ?? null,
            'is_active' => $request->boolean('is_active', $site->is_active),
        ]);

        return redirect()->route('superadmin.sites.index')
            ->with('success', 'Site ' . $site->code . ' berhasil diupdate.');
    }

    public function destroy(Request $request, Site $site)
    {
        UnitScope::for($request->user())->ensureSite($site);

        $dependentCount = $site->vehicleDetails()->count()
            + $site->users()->count()
            + $site->employeeDefaults()->count()
            + $site->lunchTransactions()->count();

        if ($dependentCount > 0) {
            return redirect()->route('superadmin.sites.index')
                ->with('error', "Site {$site->code} masih digunakan oleh {$dependentCount} data terkait. Tidak dapat dihapus.");
        }

        $site->delete();

        return redirect()->route('superadmin.sites.index')
            ->with('success', "Site {$site->code} berhasil dihapus.");
    }
}