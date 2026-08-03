<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * QR Attendance melayani tiga lokasi: PMU, PTH (Patuha), dan BCMP.
     * PMU dan Patuha sudah ada di master sites; BCMP ditambahkan di sini.
     * Karena sites sudah menjadi master table, penambahan lokasi cukup
     * lewat data — tidak ada enum/schema yang perlu diubah.
     */
    public function up(): void
    {
        $exists = DB::table('sites')->where('code', 'BCMP')->exists();

        if (! $exists) {
            DB::table('sites')->insert([
                'code'       => 'BCMP',
                'name'       => 'Basecamp',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $siteId = DB::table('sites')->where('code', 'BCMP')->value('id');

        if (! $siteId) {
            return;
        }

        // Bila sudah ada data yang menunjuk BCMP (transaksi, distribusi,
        // reservasi), site tidak boleh dihapus — cukup dinonaktifkan agar
        // FK dan histori tetap utuh.
        $referenced = collect(['lunch_transactions', 'lunch_distributions', 'lunch_guest_reservations'])
            ->contains(fn ($table) => Schema::hasTable($table)
                && DB::table($table)->where('site_id', $siteId)->exists());

        if ($referenced) {
            DB::table('sites')->where('id', $siteId)->update(['is_active' => false]);
        } else {
            DB::table('sites')->where('id', $siteId)->delete();
        }
    }
};
