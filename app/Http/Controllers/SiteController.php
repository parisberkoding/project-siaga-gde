<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Unit;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Site / Lokasi';

        $search = $request->get('search');
        $unitId = $request->get('unit_id');

        $query = Site::with('unit')
            ->withCount(['vehicleDetails', 'users', 'employeeDefaults'])
            ->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        $sites = $query->paginate(20);
        $units = Unit::orderBy('name')->get();

        return view('superadmin.sites.index', compact('title', 'sites', 'units', 'search', 'unitId'));
    }

    public function create()
    {
        $title = 'Tambah Site';
        $units = Unit::orderBy('name')->get();

        return view('superadmin.sites.form', compact('title', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|alpha_dash|unique:sites,code|max:20',
            'name' => 'required|max:100',
            'unit_id' => 'required|exists:units,id',
            'province_slug' => 'nullable|max:100',
            'is_active' => 'boolean',
        ], [
            'code.required' => 'Kode site harus diisi',
            'code.alpha_dash' => 'Kode site hanya huruf, angka, dash, underscore',
            'code.unique' => 'Kode site sudah digunakan',
            'name.required' => 'Nama site harus diisi',
            'unit_id.required' => 'Site harus terhubung ke unit',
        ]);

        Site::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'unit_id' => $validated['unit_id'],
            'province_slug' => $validated['province_slug'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('superadmin.sites.index')
            ->with('success', 'Site ' . strtoupper($validated['code']) . ' berhasil dibuat.');
    }

    public function edit(Site $site)
    {
        $title = 'Edit Site';
        $units = Unit::orderBy('name')->get();

        return view('superadmin.sites.form', compact('title', 'site', 'units'));
    }

    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'unit_id' => 'required|exists:units,id',
            'province_slug' => 'nullable|max:100',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama site harus diisi',
            'unit_id.required' => 'Site harus terhubung ke unit',
        ]);

        $site->update([
            'name' => $validated['name'],
            'unit_id' => $validated['unit_id'],
            'province_slug' => $validated['province_slug'] ?? null,
            'is_active' => $request->boolean('is_active', $site->is_active),
        ]);

        return redirect()->route('superadmin.sites.index')
            ->with('success', 'Site ' . $site->code . ' berhasil diupdate.');
    }

    public function destroy(Site $site)
    {
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