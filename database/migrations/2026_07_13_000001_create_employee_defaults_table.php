<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master karyawan untuk Lunch Management (Employee Default).
     *
     * Sebelumnya data "siapa yang makan siang" tersebar di dua tempat:
     * users.default_site_id (karyawan yang punya akun) dan tabel
     * pekerja_patuha (karyawan non-sistem). Lunch V2 butuh SATU master
     * yang berdiri sendiri — tidak tergantung akun login dan tidak
     * tergantung Vehicle Tracking — karena QR Attendance bersifat publik
     * dan Identity Resolution me-resolve nama ke master ini, bukan ke
     * tabel users.
     *
     * user_id tetap ada (nullable) hanya sebagai jembatan ke akun login
     * bila karyawan tersebut punya akun; bukan dependency wajib.
     */
    public function up(): void
    {
        Schema::create('employee_defaults', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();

            $table->string('employee_code', 50)->nullable()->unique();
            $table->string('employee_name', 150);
            $table->string('division', 100)->nullable();
            $table->string('position', 100)->nullable();

            $table->foreignId('default_site_id')->nullable()
                ->constrained('sites')->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('employee_name');
        });

        // ── Bootstrap data dari sumber lama ──────────────────────────────
        // 1. User SIAGA (manager, non_ga_staff) — dulunya sumber
        //    "default SIAGA" pada forecast lunch.
        $now = now();

        $users = DB::table('users')
            ->whereNull('deleted_at')
            ->whereIn('role', ['manager', 'non_ga_staff'])
            ->get(['id', 'user_code', 'nama_lengkap', 'jabatan', 'default_site_id']);

        foreach ($users as $u) {
            DB::table('employee_defaults')->insert([
                'user_id'         => $u->id,
                'employee_code'   => $u->user_code,
                'employee_name'   => $u->nama_lengkap,
                'position'        => $u->jabatan,
                'default_site_id' => $u->default_site_id,
                'is_active'       => true,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }

        // 2. Pekerja Patuha (karyawan non-sistem) — tabelnya di-drop pada
        //    migration berikutnya setelah datanya dipindah ke sini.
        if (Schema::hasTable('pekerja_patuha')) {
            $siteIdByLocation = ['PMU' => 1, 'Patuha' => 2];

            foreach (DB::table('pekerja_patuha')->get() as $p) {
                DB::table('employee_defaults')->insert([
                    'user_id'         => null,
                    'employee_code'   => $p->kode_pekerja,
                    'employee_name'   => $p->nama_lengkap,
                    'position'        => 'Pekerja Patuha',
                    'default_site_id' => $siteIdByLocation[$p->default_location] ?? null,
                    'is_active'       => true,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_defaults');
    }
};
