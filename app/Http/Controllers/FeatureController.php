<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\Module;
use Illuminate\Http\Request;

/**
 * Kelola tahap rilis fitur (development <-> production).
 *
 * Hanya untuk pemegang permission `features.manage` (role developers, dan
 * superadmin yang memang memegang seluruh permission).
 *
 * Semantiknya diingat baik-baik: menaikkan sebuah fitur ke production berarti
 * membukanya untuk seluruh user yang berhak; menurunkannya ke development
 * berarti menyembunyikannya dari user biasa sampai selesai diperbaiki.
 */
class FeatureController extends Controller
{
    public function index()
    {
        $title = 'Tahap Fitur (Development / Production)';

        $features = Feature::with(['module', 'activator'])
            ->orderByRaw('module_id IS NOT NULL')
            ->orderBy('module_id')
            ->orderBy('name')
            ->get();

        $modules = Module::orderBy('order')->get();

        return view('superadmin.features.index', compact('title', 'features', 'modules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Kode fitur memakai titik (mis. spd.lpj), jadi bukan alpha_dash
            // (yang menolak titik). Batasi ke huruf kecil, angka, titik, dash,
            // dan underscore.
            'code' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9._-]+$/', 'unique:features,code'],
            'name' => 'required|max:150',
            'description' => 'nullable|max:255',
            'module_id' => 'nullable|exists:modules,id',
            'stage' => 'nullable|in:development,production',
        ], [
            'code.required' => 'Kode fitur harus diisi',
            'code.regex' => 'Kode fitur hanya boleh huruf kecil, angka, titik, dash, underscore',
            'code.unique' => 'Kode fitur sudah terdaftar',
            'name.required' => 'Nama fitur harus diisi',
        ]);

        $feature = Feature::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'module_id' => $validated['module_id'] ?? null,
            // Fitur baru default development — kalau langsung production,
            // halaman yang belum siap akan terbuka tanpa sempat direview.
            'stage' => $validated['stage'] ?? Feature::STAGE_DEVELOPMENT,
        ]);

        return redirect()->route('superadmin.features.index')
            ->with('success', "Fitur \"{$feature->name}\" didaftarkan dalam tahap {$feature->stage}.");
    }

    /**
     * Naikkan ke production — terbuka untuk seluruh user yang berhak.
     */
    public function activate(Feature $feature)
    {
        $feature->activate(auth()->id());

        return redirect()->route('superadmin.features.index')
            ->with('success', "Fitur \"{$feature->name}\" dinaikkan ke PRODUCTION.");
    }

    /**
     * Turunkan ke development — disembunyikan dari user biasa.
     */
    public function deactivate(Feature $feature)
    {
        $feature->deactivate(auth()->id());

        return redirect()->route('superadmin.features.index')
            ->with('warning', "Fitur \"{$feature->name}\" diturunkan ke DEVELOPMENT.");
    }
}
