<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Gate Modul per Unit — seeder.
 *
 * Mendefinisikan 8 modul (satu modul = satu siklus bisnis utuh: input ->
 * proses -> report -> dashboard), memetakan permission ke modul, lalu
 * mengisi matrix unit x modul.
 *
 * PENTING — strategi anti-outage:
 * Unit yang SUDAH ADA (PUSAT, PATUHA, DIENG) di-grandfather dengan SEMUA modul
 * enabled. Kalau tidak, hari deploy unit Patuha & Dieng langsung kehilangan
 * Kendaraan & Alat (dispatcher tidak bisa pengecekan) dan Lunch (vendor
 * catering tidak bisa lihat kehadiran). Pusat mematikan yang tidak relevan
 * secara sadar lewat UI matrix.
 *
 * Unit yang dibuat SETELAH ini mengikuti `default_enabled` (BBM + SPD saja).
 */
class ModuleSeeder extends Seeder
{
    /**
     * Definisi modul.
     * gateable=false + always_on=true dipakai untuk modul administratif yang
     * tidak masuk checklist unit (RBAC & Pengaturan).
     */
    private const MODULES = [
        [
            'code' => 'bbm',
            'name' => 'BBM & Permohonan',
            'description' => 'Siklus BBM: permohonan -> approval -> bukti pembelian -> penarikan laporan.',
            'icon' => 'bi bi-fuel-pump',
            'order' => 1,
            'is_gateable' => true,
            'is_always_on' => false,
            'default_enabled' => true,
        ],
        [
            'code' => 'spd',
            'name' => 'Perjalanan Dinas (SPD & LPJ)',
            'description' => 'Siklus perjalanan dinas: surat tugas -> validasi -> SPD berjalan -> LPJ -> validasi berjenjang.',
            'icon' => 'bi bi-envelope-paper',
            'order' => 2,
            'is_gateable' => true,
            'is_always_on' => false,
            'default_enabled' => true,
        ],
        [
            'code' => 'vehicle',
            'name' => 'Kendaraan & Alat',
            'description' => 'Siklus kendaraan: master -> aktivasi -> pengecekan -> VPR/service -> laporan kesehatan.',
            'icon' => 'bi bi-truck',
            'order' => 3,
            'is_gateable' => true,
            'is_always_on' => false,
            'default_enabled' => false,
        ],
        [
            'code' => 'lunch',
            'name' => 'Lunch Management',
            'description' => 'Siklus lunch: master pekerja -> forecast -> distribusi -> transaksi kehadiran.',
            'icon' => 'bi bi-egg-fried',
            'order' => 4,
            'is_gateable' => true,
            'is_always_on' => false,
            'default_enabled' => false,
        ],
        [
            'code' => 'ai',
            'name' => 'AI Assistant',
            'description' => 'Asisten AI & analisis struk (OCR).',
            'icon' => 'bi bi-robot',
            'order' => 5,
            'is_gateable' => true,
            'is_always_on' => false,
            'default_enabled' => false,
        ],
        [
            'code' => 'users',
            'name' => 'Manajemen Pengguna',
            'description' => 'Kelola akun: buat, ubah, reset password, nonaktifkan.',
            'icon' => 'bi bi-people',
            'order' => 6,
            'is_gateable' => true,
            'is_always_on' => false,
            'default_enabled' => false,
        ],
        [
            'code' => 'rbac',
            'name' => 'RBAC & Hak Akses',
            'description' => 'Kelola role & permission. Selalu aktif lintas unit.',
            'icon' => 'bi bi-shield-lock',
            'order' => 7,
            'is_gateable' => false,
            'is_always_on' => true,
            'default_enabled' => true,
        ],
        [
            'code' => 'settings',
            'name' => 'Pengaturan & Data Master',
            'description' => 'Master gasoline, unit, site. Selalu aktif lintas unit.',
            'icon' => 'bi bi-gear',
            'order' => 8,
            'is_gateable' => false,
            'is_always_on' => true,
            'default_enabled' => true,
        ],
    ];

    /**
     * Pemetaan permission -> modul.
     *
     * Sengaja ditulis EKSPLISIT, bukan diturunkan dari prefix kode permission.
     * Alasannya: `pelaporan.*` dipakai bersama oleh dua siklus (halaman
     * /pelaporan menaungi mode BBM operasional DAN mode SPD di bawah satu
     * permission yang sama), sehingga prefix tidak dapat diandalkan.
     *
     * `dashboard.view` di-attribute ke tiap modul siklus, karena dashboard
     * adalah bagian dari siklus (bukan modul tersendiri). Konsekuensinya
     * dashboard masih dapat diakses selama MINIMAL SATU siklus enabled.
     */
    private const MODULE_PERMISSIONS = [
        // Modul BBM: seluruh permission bbm + mode operasional /pelaporan + dashboard
        'bbm' => [
            'bbm.view', 'bbm.request', 'bbm.report', 'bbm.approve', 'bbm.export',
            'pelaporan.view', 'pelaporan.create', 'pelaporan.analyze',
            'dashboard.view',
        ],

        // Modul SPD: spd + lpj + mode SPD /pelaporan + dashboard
        'spd' => [
            'spd.view', 'spd.create', 'spd.update', 'spd.delete', 'spd.approve',
            'spd.assign', 'spd.manage', 'spd.validate_trip',
            'lpj.view', 'lpj.create', 'lpj.update', 'lpj.delete',
            'lpj.validate', 'lpj.validate_management', 'lpj.export',
            'pelaporan.view', 'pelaporan.create', 'pelaporan.analyze',
            'dashboard.view',
        ],

        // Modul Kendaraan & Alat
        'vehicle' => [
            'vehicle.view', 'vehicle.create', 'vehicle.update', 'vehicle.delete',
            'vehicle.approve', 'vehicle.checking', 'vehicle.scan',
            'vehicle.acknowledge', 'vehicle.export', 'vehicle.report',
            'vehicle.master', 'vehicle.sparepart',
            'dashboard.view',
        ],

        // Modul Lunch
        'lunch' => [
            'lunch.view', 'lunch.export', 'lunch.manage',
            'dashboard.view',
        ],

        // Modul AI
        'ai' => [
            'ai.view',
            'dashboard.view',
        ],

        // Modul Manajemen Pengguna
        'users' => [
            'users.view', 'users.create', 'users.update', 'users.delete',
        ],

        // Modul RBAC (selalu aktif)
        'rbac' => [
            'rbac.view', 'rbac.roles', 'rbac.permissions',
        ],

        // Modul Pengaturan (selalu aktif)
        'settings' => [
            'settings.view', 'settings.manage', 'settings.modules', 'settings.sso_mapping', 'features.manage',
        ],
    ];

    public function run(): void
    {
        $this->seedModules();
        $this->mapPermissions();
        $this->seedUnitModules();
    }

    private function seedModules(): void
    {
        foreach (self::MODULES as $def) {
            Module::updateOrCreate(['code' => $def['code']], $def);
        }
    }

    private function mapPermissions(): void
    {
        foreach (self::MODULE_PERMISSIONS as $moduleCode => $permissionCodes) {
            $module = Module::where('code', $moduleCode)->first();
            if (! $module) {
                continue;
            }

            $permissionIds = Permission::whereIn('code', $permissionCodes)->pluck('id')->all();

            // sync() agar pencabutan permission dari definisi ikut terhapus.
            $module->permissions()->sync($permissionIds);
        }
    }

    /**
     * Isi matrix unit x modul untuk unit yang BELUM punya baris sama sekali.
     *
     * Unit yang belum punya baris diperlakukan sebagai unit eksisting dan
     * di-grandfather FULL-ENABLE (anti-outage). Unit yang dibuat setelah fitur
     * ini hidup sudah mendapat barisnya sendiri dari UnitController mengikuti
     * `default_enabled`, sehingga selalu punya baris dan dilewati di sini.
     *
     * Idempotent: unit yang sudah punya baris TIDAK disentuh, supaya keputusan
     * Pusat (mis. mematikan Kendaraan untuk unit tertentu) tidak dikembalikan
     * setiap kali seeder dijalankan ulang.
     *
     * Catatan: modul always-on (RBAC & Pengaturan) juga ditulis sebagai
     * is_enabled=true agar matrix di UI menampilkan keadaan sebenarnya, bukan
     * baris yang hilang.
     */
    private function seedUnitModules(): void
    {
        $modules = Module::orderBy('order')->get();
        $now = now();

        foreach (Unit::all() as $unit) {
            $sudahPunyaBaris = DB::table('unit_modules')
                ->where('unit_id', $unit->id)
                ->exists();

            if ($sudahPunyaBaris) {
                continue;
            }

            $rows = $modules->map(fn (Module $module) => [
                'unit_id' => $unit->id,
                'module_id' => $module->id,
                'is_enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            if (! empty($rows)) {
                DB::table('unit_modules')->insert($rows);
            }
        }
    }
}
