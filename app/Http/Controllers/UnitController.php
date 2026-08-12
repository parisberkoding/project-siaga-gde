<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $title = 'Unit Bisnis';
        $units = Unit::withCount(['sites', 'users'])
            ->with('sites')
            ->orderBy('name')
            ->get();

        return view('superadmin.units.index', compact('title', 'units'));
    }

    public function create()
    {
        $title = 'Tambah Unit Bisnis';

        return view('superadmin.units.form', compact('title'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|alpha_dash|unique:units,code|max:50',
            'name' => 'required|max:100',
            'description' => 'nullable|max:500',
            'is_active' => 'boolean',
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

        return redirect()->route('superadmin.units.index')
            ->with('success', "Unit {$unit->name} berhasil dibuat.");
    }

    public function edit(Unit $unit)
    {
        $title = 'Edit Unit Bisnis';
        $unit->loadCount('sites');

        return view('superadmin.units.form', compact('title', 'unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|max:100',
            'description' => 'nullable|max:500',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama unit harus diisi',
        ]);

        $unit->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', $unit->is_active),
        ]);

        return redirect()->route('superadmin.units.index')
            ->with('success', "Unit {$unit->name} berhasil diupdate.");
    }

    public function destroy(Unit $unit)
    {
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
}
