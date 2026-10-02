<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SsoRoleMapping;
use App\Models\SsoSyncLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Pemetaan jabatan SSO -> role.
 *
 * Menjawab pertanyaan operasional: "SSO mengirim jabatan 'Asisten Manager',
 * lalu di SIAGA jabatan itu bisa mengakses apa saja?"
 *
 * Jawabannya selalu per KOMBINASI (jabatan x unit), karena dua orang dengan
 * jabatan sama di unit berbeda bisa membutuhkan hak akses berbeda. Jumlah
 * kombinasinya kecil (3 jabatan x 3-4 unit), sehingga antrian review-nya
 * dihitung per kombinasi — BUKAN per user. Dengan begitu, walau ribuan
 * karyawan login di minggu pertama, antriannya tetap belasan baris.
 */
class SsoRoleMappingController extends Controller
{
    public function index()
    {
        $title = 'Pemetaan Jabatan SSO';

        $units = Unit::orderBy('name')->get();
        $roles = Role::where('is_active', true)->withCount('permissions')->orderBy('name')->get();

        $mappings = SsoRoleMapping::with(['unit', 'role'])
            ->orderBy('unit_id')
            ->orderBy('jabatan')
            ->get();

        return view('superadmin.sso.mappings', compact('title', 'units', 'roles', 'mappings') + [
            'pending' => $this->pendingCombinations(),
            'conflicts' => $this->conflicts(),
            'pendingUpgrades' => $this->pendingUpgrades(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jabatan' => 'required|max:100',
            'unit_id' => 'required|exists:units,id',
            'role_id' => 'required|exists:roles,id',
            'note' => 'nullable|max:255',
        ], [
            'jabatan.required' => 'Jabatan harus diisi',
            'unit_id.required' => 'Unit harus dipilih',
            'role_id.required' => 'Role harus dipilih',
        ]);

        // jabatan_key diturunkan otomatis oleh model, sehingga "Asisten manager"
        // dan "Asisten Manager" tidak menjadi dua baris berbeda.
        $key = SsoRoleMapping::normalizeJabatan($validated['jabatan']);

        $exists = SsoRoleMapping::where('jabatan_key', $key)
            ->where('unit_id', $validated['unit_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()
                ->with('error', "Pemetaan untuk jabatan \"{$validated['jabatan']}\" di unit tersebut sudah ada.");
        }

        $mapping = SsoRoleMapping::create([
            'jabatan' => trim($validated['jabatan']),
            'unit_id' => $validated['unit_id'],
            'role_id' => $validated['role_id'],
            'is_active' => true,
            'note' => $validated['note'] ?? null,
        ]);

        SsoSyncLog::record(
            SsoSyncLog::DECISION_SYNCED,
            userId: auth()->id(),
            mappedRoleId: $mapping->role_id,
            note: "Pemetaan dibuat: {$mapping->jabatan} @ unit#{$mapping->unit_id} -> role#{$mapping->role_id}",
            provider: (string) config('sso.provider_name', 'geodipa'),
        );

        return redirect()->route('superadmin.sso.mappings.index')
            ->with('success', "Pemetaan \"{$mapping->jabatan}\" berhasil dibuat.");
    }

    public function update(Request $request, SsoRoleMapping $mapping)
    {
        $validated = $request->validate([
            'jabatan' => 'required|max:100',
            'unit_id' => 'required|exists:units,id',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
            'note' => 'nullable|max:255',
        ], [
            'jabatan.required' => 'Jabatan harus diisi',
            'unit_id.required' => 'Unit harus dipilih',
            'role_id.required' => 'Role harus dipilih',
        ]);

        $key = SsoRoleMapping::normalizeJabatan($validated['jabatan']);

        $duplicate = SsoRoleMapping::where('jabatan_key', $key)
            ->where('unit_id', $validated['unit_id'])
            ->where('id', '!=', $mapping->id)
            ->exists();

        if ($duplicate) {
            return redirect()->back()->withInput()
                ->with('error', 'Sudah ada pemetaan lain untuk jabatan & unit tersebut.');
        }

        $mapping->update([
            'jabatan' => trim($validated['jabatan']),
            'unit_id' => $validated['unit_id'],
            'role_id' => $validated['role_id'],
            'is_active' => $request->boolean('is_active'),
            'note' => $validated['note'] ?? null,
        ]);

        SsoSyncLog::record(
            SsoSyncLog::DECISION_SYNCED,
            userId: auth()->id(),
            mappedRoleId: $mapping->role_id,
            note: "Pemetaan diubah: {$mapping->jabatan} @ unit#{$mapping->unit_id} -> role#{$mapping->role_id}"
                .($mapping->is_active ? '' : ' (dinonaktifkan)'),
            provider: (string) config('sso.provider_name', 'geodipa'),
        );

        return redirect()->route('superadmin.sso.mappings.index')
            ->with('success', "Pemetaan \"{$mapping->jabatan}\" berhasil diupdate.");
    }

    public function destroy(SsoRoleMapping $mapping)
    {
        $label = $mapping->jabatan;
        $mapping->delete();

        SsoSyncLog::record(
            SsoSyncLog::DECISION_SYNCED,
            userId: auth()->id(),
            note: "Pemetaan dihapus: {$label}",
            provider: (string) config('sso.provider_name', 'geodipa'),
        );

        return redirect()->route('superadmin.sso.mappings.index')
            ->with('success', "Pemetaan \"{$label}\" berhasil dihapus.");
    }

    /* =========================================================
     * Antrian review
     * =======================================================*/

    /**
     * Kombinasi (jabatan x unit) yang belum punya pemetaan.
     *
     * Diturunkan dari user SSO yang belum punya role — bukan dari log, supaya
     * datanya pasti mencerminkan keadaan nyata. Dikelompokkan per kombinasi
     * agar yang perlu diisi Pusat hanya belasan baris, bukan ribuan user.
     */
    private function pendingCombinations()
    {
        return User::query()
            ->where('auth_provider', 'sso')
            ->whereDoesntHave('roles')
            ->with('unit')
            ->get()
            ->groupBy(fn (User $u) => SsoRoleMapping::normalizeJabatan($u->jabatan).'|'.($u->unit_id ?? 'null'))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'jabatan' => $first->jabatan,
                    'unit' => $first->unit,
                    'has_unit' => $first->unit_id !== null,
                    'user_count' => $group->count(),
                ];
            })
            ->sortByDesc('user_count')
            ->values();
    }

    /**
     * Akun nonaktif di SIAGA tetapi SSO masih menganggapnya aktif.
     * Pusat yang memutuskan; sistem tidak mengubah status apa pun otomatis.
     */
    private function conflicts()
    {
        return SsoSyncLog::query()
            ->where('decision', SsoSyncLog::DECISION_CONFLICT)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }

    /**
     * Perubahan role yang ditahan karena menaikkan hak akses (upgrade).
     */
    private function pendingUpgrades()
    {
        return SsoSyncLog::query()
            ->where('decision', SsoSyncLog::DECISION_PENDING_REVIEW)
            ->with(['user', 'user.unit'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
    }
}
