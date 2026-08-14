<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Phase 2 - RBAC Seeder
 *
 * Membangun Permission Group, Permission, Role, lalu melakukan dual-write:
 * memetakan nilai kolom `role` lama (string) ke tabel `user_roles` agar
 * aplikasi langsung berfungsi tanpa breaking change.
 */
class RbacSeeder extends Seeder
{
    // Mapping role lama (kolom User.role) -> code role RBAC
    private const ROLE_MAP = [
        'superadmin' => 'superadmin',
        'admin' => 'general_affairs',
        'admin_bbm' => 'general_affairs',
        'requester' => 'requester',
        'manager' => 'manager',
        'dispatcher' => 'dispatcher',
        'non_ga_staff' => 'view_only',
        'developer' => 'superadmin',
    ];

    public function run(): void
    {
        $this->seedPermissionGroups();
        $this->seedPermissions();
        $this->seedRoles();
        $this->syncUserRoles();
    }

    private function seedPermissionGroups(): void
    {
        $groups = [
            ['code' => 'dashboard', 'name' => 'Dashboard',        'icon' => 'bi bi-speedometer2', 'order' => 1],
            ['code' => 'bbm',       'name' => 'BBM / Permohonan', 'icon' => 'bi bi-fuel-pump',    'order' => 2],
            ['code' => 'spd',       'name' => 'Perjalanan Dinas', 'icon' => 'bi bi-car-front',    'order' => 3],
            ['code' => 'lpj',       'name' => 'LPJ',              'icon' => 'bi bi-receipt',      'order' => 4],
            ['code' => 'pelaporan', 'name' => 'Pelaporan',        'icon' => 'bi bi-journal-text', 'order' => 5],
            ['code' => 'vehicle',   'name' => 'Kendaraan & Alat', 'icon' => 'bi bi-truck',        'order' => 6],
            ['code' => 'users',     'name' => 'Manajemen User',   'icon' => 'bi bi-people',       'order' => 8],
            ['code' => 'rbac',      'name' => 'RBAC & Hak Akses', 'icon' => 'bi bi-shield-lock',  'order' => 9],
            ['code' => 'lunch',     'name' => 'Lunch Management', 'icon' => 'bi bi-egg-fried',    'order' => 11],
            ['code' => 'settings',  'name' => 'Pengaturan',       'icon' => 'bi bi-gear',         'order' => 10],
        ];

        foreach ($groups as $g) {
            PermissionGroup::updateOrCreate(['code' => $g['code']], $g);
        }
    }

    private function seedPermissions(): void
    {
        // Definisi permission per module.
        // Setiap entry: [module, action, group_code, description]
        $defs = [
            // Dashboard
            ['dashboard', 'view', 'dashboard', 'Melihat dashboard'],

            // BBM
            ['bbm', 'view',     'bbm', 'Melihat permohonan BBM'],
            ['bbm', 'request',  'bbm', 'Membuat permohonan BBM'],
            ['bbm', 'report',   'bbm', 'Melaporkan bukti pembelian BBM'],
            ['bbm', 'approve',  'bbm', 'Menyetujui permohonan BBM'],
            ['bbm', 'export',   'bbm', 'Export data BBM'],

            // SPD
            ['spd', 'view',      'spd', 'Melihat surat tugas'],
            ['spd', 'create',    'spd', 'Membuat surat tugas'],
            ['spd', 'update',    'spd', 'Mengubah surat tugas'],
            ['spd', 'delete',    'spd', 'Menghapus surat tugas'],
            ['spd', 'approve',   'spd', 'Menyetujui surat tugas'],
            ['spd', 'assign',    'spd', 'Assign driver/kendaraan'],

            // LPJ
            ['lpj', 'view',    'lpj', 'Melihat LPJ'],
            ['lpj', 'create',  'lpj', 'Membuat LPJ'],
            ['lpj', 'update',  'lpj', 'Mengubah LPJ'],
            ['lpj', 'delete',  'lpj', 'Menghapus LPJ'],
            ['lpj', 'validate', 'lpj', 'Memvalidasi LPJ'],
            ['lpj', 'export',  'lpj', 'Export LPJ'],

            // Pelaporan (single page pelaporan BBM operasional & SPD + AI/OCR struk)
            ['pelaporan', 'view',   'pelaporan', 'Melihat halaman pelaporan'],
            ['pelaporan', 'create', 'pelaporan', 'Membuat pelaporan (SPD & operasional)'],
            ['pelaporan', 'analyze', 'pelaporan', 'Analisis struk dengan AI/OCR'],

            // Vehicle
            ['vehicle', 'view',      'vehicle', 'Melihat kendaraan & alat'],
            ['vehicle', 'create',    'vehicle', 'Menambah kendaraan & alat'],
            ['vehicle', 'update',    'vehicle', 'Mengubah kendaraan & alat'],
            ['vehicle', 'delete',    'vehicle', 'Menghapus kendaraan & alat'],
            ['vehicle', 'approve',   'vehicle', 'Menyetujui kendaraan & alat'],
            ['vehicle', 'checking',  'vehicle', 'Input body & daily checking'],
            ['vehicle', 'master',    'vehicle', 'Kelola Master Vehicle (budget, site, kuota BBM)'],
            ['vehicle', 'sparepart', 'vehicle', 'Kelola sparepart request & repair'],

            // Users
            ['users', 'view',   'users', 'Melihat user'],
            ['users', 'create', 'users', 'Membuat user'],
            ['users', 'update', 'users', 'Mengubah user'],
            ['users', 'delete', 'users', 'Menghapus user'],

            // RBAC
            ['rbac', 'view',        'rbac', 'Melihat role & permission'],
            ['rbac', 'roles',       'rbac', 'Kelola role'],
            ['rbac', 'permissions', 'rbac', 'Kelola permission'],

            // Lunch Management
            ['lunch', 'view',   'lunch', 'Melihat data lunch (kehadiran, forecast, distribusi)'],
            ['lunch', 'export', 'lunch', 'Export data lunch'],
            ['lunch', 'manage', 'lunch', 'Kelola employee defaults lunch'],

            // Settings
            ['settings', 'view',   'settings', 'Melihat pengaturan'],
            ['settings', 'manage', 'settings', 'Mengelola pengaturan'],
        ];

        foreach ($defs as [$module, $action, $groupCode, $desc]) {
            $group = PermissionGroup::where('code', $groupCode)->first();
            Permission::updateOrCreate(
                ['code' => "{$module}.{$action}"],
                [
                    'permission_group_id' => $group?->id,
                    'module' => $module,
                    'action' => $action,
                    'description' => $desc,
                ]
            );
        }
    }

    private function seedRoles(): void
    {
        $roles = [
            'superadmin' => [
                'name' => 'Super Admin',
                'description' => 'Akses penuh ke seluruh modul & RBAC.',
                'is_system' => true,
                'permissions' => Permission::pluck('code')->toArray(), // full access
            ],
            'general_affairs' => [
                'name' => 'General Affairs',
                'description' => 'Kelola BBM, SPD, LPJ, Vehicle, & transaksi. Tanpa user management.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'bbm.view', 'bbm.request', 'bbm.report', 'bbm.approve', 'bbm.export',
                    'spd.view', 'spd.create', 'spd.update', 'spd.delete', 'spd.approve', 'spd.assign',
                    'lpj.view', 'lpj.create', 'lpj.update', 'lpj.delete', 'lpj.validate', 'lpj.export',
                    'vehicle.view', 'vehicle.create', 'vehicle.update', 'vehicle.delete',
                    'vehicle.approve', 'vehicle.master',
                    'lunch.view', 'lunch.export',
                    'settings.view',
                ],
            ],
            'dispatcher' => [
                'name' => 'Dispatcher',
                'description' => 'Fokus operasional: SPD, vehicle checking, approval harian.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'spd.view', 'spd.create', 'spd.update', 'spd.approve', 'spd.assign',
                    'vehicle.view', 'vehicle.checking', 'vehicle.approve',
                    'bbm.view', 'bbm.approve',
                    'pelaporan.view',
                ],
            ],
            'requester' => [
                'name' => 'Requester / Driver',
                'description' => 'Driver: request BBM, lapor bukti, buat & laporkan LPJ SPD.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'bbm.view', 'bbm.request', 'bbm.report',
                    'spd.view',
                    'lpj.view', 'lpj.create', 'lpj.update',
                    'pelaporan.view', 'pelaporan.create', 'pelaporan.analyze',
                    'vehicle.view',
                ],
            ],
            'manager' => [
                'name' => 'Manager',
                'description' => 'Approval & review, tanpa CRUD master.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'bbm.view', 'bbm.approve', 'bbm.export',
                    'spd.view', 'spd.approve',
                    'lpj.view', 'lpj.validate', 'lpj.export',
                    'vehicle.view',
                ],
            ],
            'view_only' => [
                'name' => 'View Only',
                'description' => 'Read-only ke seluruh dashboard & data.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'bbm.view', 'spd.view', 'lpj.view', 'vehicle.view', 'pelaporan.view',
                    'lunch.view',
                ],
            ],
            'vendor' => [
                'name' => 'Vendor Catering',
                'description' => 'Akses terbatas: melihat daftar kehadiran lunch harian.',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'lunch.view',
                ],
            ],
        ];

        foreach ($roles as $code => $data) {
            $role = Role::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                    'is_system' => $data['is_system'],
                ]
            );
            $role->syncPermissionsByCode($data['permissions']);
        }
    }

    /**
     * Dual-write: petakan kolom `role` lama ke user_roles.
     * Jika user punya role lama yang tidak di-map, fallback ke view_only.
     */
    private function syncUserRoles(): void
    {
        $users = User::withTrashed()->get();

        foreach ($users as $user) {
            $legacy = strtolower($user->role ?? '');
            $rbacCode = self::ROLE_MAP[$legacy] ?? 'view_only';

            $role = Role::where('code', $rbacCode)->first();
            if (! $role) {
                $role = Role::where('code', 'view_only')->first();
            }

            if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
                $user->roles()->attach($role->id, ['is_primary' => true]);
            }
        }
    }
}
