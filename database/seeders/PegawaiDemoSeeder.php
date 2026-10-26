<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Site;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun demo "pegawai" (non-GA) untuk pengujian alur validasi perjalanan dinas
 * oleh pengguna yang berangkat.
 *
 * SENGAJA TIDAK didaftarkan di DatabaseSeeder: akun ini memakai password yang
 * diketahui bersama, sehingga tidak boleh ikut ter-seed otomatis (mis. saat
 * boot pertama container). Jalankan manual saat butuh:
 *
 *     php artisan db:seed --class=PegawaiDemoSeeder
 *
 * Kenapa ada akun ini:
 *   - Driver (`requester`) sudah tidak lagi memegang `spd.validate_trip`,
 *     sehingga menu "Validasi Perjalanan (User)" hilang dari sisi driver dan
 *     tahap validasi pengguna butuh akun terpisah.
 *   - Role `pegawai` adalah role tujuan pemetaan jabatan SSO untuk pegawai
 *     umum; akun ini menjadi contoh nyata sebelum SSO aktif.
 *
 * CATATAN KEAMANAN: ganti/hapus akun ini sebelum dipakai di produksi.
 */
class PegawaiDemoSeeder extends Seeder
{
    private const USERNAME = 'Faris';

    private const PASSWORD = 'Faris123#@';

    /**
     * Unit akun demo SENGAJA dikosongkan (null), mengikuti akun-akun uji lokal
     * lain (adminx, driver, superadmin, dst.).
     *
     * Alasannya: `LpjPenggantian::scopeInUnit()` membatasi daftar LPJ ke unit
     * surat SPD-nya. Data demo di repo ini tidak mengisi `surat_spds.unit_id`,
     * sehingga akun ber-unit akan melihat daftar validasi KOSONG dan alur
     * validasi pengguna tidak bisa diuji. Dengan unit null, scope tidak
     * diterapkan (perilaku transisi yang sama dengan akun uji lain).
     *
     * Saat SSO aktif, unit diisi dari klaim SSO — bukan dari seeder ini.
     */
    private const SITE_CODE = 'PTH';

    /** Prefix kode user, mengikuti pola Manajemen User: {prefix}.{urutan}.{lokasi}. */
    private const USER_CODE_PREFIX = 'P00';

    public function run(): void
    {
        $role = Role::where('code', 'pegawai')->first();

        if (! $role) {
            $this->command?->warn('Role "pegawai" belum ada — jalankan migrasi/RbacSeeder lebih dulu. Seeder dilewati.');

            return;
        }

        $site = Site::where('code', self::SITE_CODE)->first();

        // PENTING: kolom `username` unik dengan collation case-insensitive
        // (utf8mb4_*_ci), sehingga "Faris" dan "faris" dianggap username yang
        // SAMA. Pencarian dilakukan eksplisit agar akun yang sudah ada tidak
        // tertimpa tanpa disadari — kalau ketemu, seeder memberi tahu.
        $user = User::whereRaw('LOWER(username) = ?', [mb_strtolower(self::USERNAME)])->first();

        if ($user) {
            $this->command?->warn(
                "Username '".self::USERNAME."' sudah dipakai akun '{$user->username}' (id {$user->id}, role lama '{$user->role}'). "
                .'Akun itu yang dipakai ulang dan rolenya diarahkan ke `pegawai`.'
            );
        }

        $user ??= new User();
        $user->fill([
            'password'        => Hash::make(self::PASSWORD),
            'nama_lengkap'    => 'Faris',
            'role'            => 'pegawai',
            'jabatan'         => 'Pegawai',
            // Sengaja dikosongkan: akun lokal/demo lain juga tanpa email, dan
            // menebak alamat email bisa bentrok dengan mailbox sungguhan.
            'email'           => null,
            'default_site_id' => $site?->id,
            'unit_id'         => null,
            'is_active'       => true,
        ]);

        if (! $user->exists) {
            $user->username = self::USERNAME;
        }

        $user->save();

        // auth_provider di luar $fillable (agar tidak bisa diubah lewat form),
        // jadi di-set langsung. 'local' = login lewat form biasa, bukan SSO.
        if ($user->auth_provider !== 'local') {
            $user->auth_provider = 'local';
            $user->save();
        }

        if (! $user->user_code || str_starts_with((string) $user->user_code, 'USR-')) {
            $user->user_code = self::USER_CODE_PREFIX.'.'.str_pad($user->id, 4, '0', STR_PAD_LEFT).'.'.($site?->code ?? 'PTH');
            $user->save();
        }

        // Satu user = satu role RBAC (source of truth permission) → replace: true
        // supaya role lama (mis. view_only) tidak tertinggal dan membuat menu
        // campur aduk saat pengujian.
        $user->assignRole($role, replace: true);

        $this->command?->info(
            'Akun demo pegawai siap: '.$user->username.' / '.self::PASSWORD
            .' (role '.$role->code.', unit '.($user->unit?->code ?? 'tanpa unit (scope bebas)').', '.$user->user_code.')'
        );
    }
}
