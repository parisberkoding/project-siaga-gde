<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Unit;

class RbacController extends Controller
{
    public function index()
    {
        $title = 'Role & Hak Akses';
        $roles = Role::withCount('users')
            ->with(['permissions', 'unit'])
            ->orderByRaw('unit_id IS NOT NULL')   // role global dulu, lalu role per unit
            ->orderBy('name')
            ->get();

        return view('superadmin.rbac.index', compact('title', 'roles'));
    }

    public function create()
    {
        $title = 'Tambah Role Baru';
        $groups = PermissionGroup::with('permissions')->orderBy('order')->get();
        $units = Unit::orderBy('name')->get();
        return view('superadmin.rbac.form', compact('title', 'groups', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => 'required|alpha_dash|unique:roles,code|max:100',
            'name'        => 'required|max:255',
            'description' => 'nullable|max:500',
            'unit_id' => 'nullable|exists:units,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'code.required'     => 'Kode role harus diisi',
            'code.alpha_dash'   => 'Kode role hanya boleh huruf, angka, dash, underscore',
            'code.unique'       => 'Kode role sudah digunakan',
            'name.required'     => 'Nama role harus diisi',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'code'        => $validated['code'],
                'name'        => Role::normalizeLabel($validated['name']),
                'description' => $validated['description'] ?? null,
                // Kosong = role global (berlaku semua unit). Diisi = role
                // khusus unit tersebut, dan hanya bisa diberikan ke user
                // di unit itu (lihat Role::isAssignableTo).
                'unit_id'     => $validated['unit_id'] ?? null,
                'is_active'   => true,
                'is_system'   => false,
            ]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->sync($validated['permissions']);
            }

            DB::commit();

            return redirect()->route('superadmin.rbac.index')
                ->with('success', "Role {$role->name} berhasil dibuat.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', safe_exception_message($e, 'Gagal membuat role. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function edit($id)
    {
        $title = 'Edit Role';
        $role = Role::with('permissions')->findOrFail($id);

        if ($role->is_system) {
            return redirect()->route('superadmin.rbac.index')
                ->with('warning', 'Role sistem tidak dapat diubah.');
        }

        $groups = PermissionGroup::with('permissions')->orderBy('order')->get();
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();
        $units = Unit::orderBy('name')->get();

        return view('superadmin.rbac.form', compact('title', 'role', 'groups', 'rolePermissionIds', 'units'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return redirect()->route('superadmin.rbac.index')
                ->with('error', 'Role sistem tidak dapat diubah.');
        }

        $validated = $request->validate([
            'name'        => 'required|max:255',
            'description' => 'nullable|max:500',
            'unit_id'     => 'nullable|exists:units,id',
            'is_active'   => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ], [
            'name.required' => 'Nama role harus diisi',
        ]);

        try {
            DB::beginTransaction();

            $role->update([
                'name'        => Role::normalizeLabel($validated['name']),
                'description' => $validated['description'] ?? null,
                'unit_id'     => $validated['unit_id'] ?? null,
                'is_active'   => $request->boolean('is_active', $role->is_active),
            ]);

            $role->permissions()->sync($validated['permissions'] ?? []);

            DB::commit();

            return redirect()->route('superadmin.rbac.index')
                ->with('success', "Role {$role->name} berhasil diupdate.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', safe_exception_message($e, 'Gagal update role. Silakan coba lagi atau hubungi admin.'));
        }
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return redirect()->route('superadmin.rbac.index')
                ->with('error', 'Role sistem tidak dapat dihapus.');
        }

        $userCount = $role->users()->count();
        if ($userCount > 0) {
            return redirect()->route('superadmin.rbac.index')
                ->with('error', "Role {$role->name} masih digunakan oleh {$userCount} user. Tidak dapat dihapus.");
        }

        try {
            $role->permissions()->detach();
            $role->delete();

            return redirect()->route('superadmin.rbac.index')
                ->with('success', "Role {$role->name} berhasil dihapus.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', safe_exception_message($e, 'Gagal hapus role. Silakan coba lagi atau hubungi admin.'));
        }
    }
}
