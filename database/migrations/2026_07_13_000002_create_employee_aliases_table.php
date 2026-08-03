<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alias pencarian untuk Identity Resolution Engine.
     *
     * User QR Attendance tidak login dan hanya mengetik nama panggilan
     * ("dedi", "pak dedi", "dedi ga"). Alias disimpan dalam bentuk yang
     * SUDAH dinormalisasi (lowercase, tanpa gelar/tanda baca) sehingga
     * lookup cukup satu index scan, bukan LIKE mahal ke master.
     *
     * source: 'manual' (diinput admin) atau 'learned' (dipelajari otomatis
     * dari transaksi yang berhasil di-resolve dengan confidence tinggi).
     */
    public function up(): void
    {
        Schema::create('employee_aliases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_default_id')
                ->constrained('employee_defaults')->cascadeOnDelete();

            $table->string('alias', 150);
            $table->string('source', 20)->default('manual'); // manual|learned

            $table->timestamps();

            $table->unique(['employee_default_id', 'alias']);
            $table->index('alias');
        });

        // Bootstrap: nama lengkap (dinormalisasi sederhana) selalu menjadi
        // alias pertama supaya pencarian langsung jalan tanpa input admin.
        $now = now();
        foreach (DB::table('employee_defaults')->get(['id', 'employee_name']) as $e) {
            $alias = mb_strtolower(trim(preg_replace('/\s+/', ' ', $e->employee_name)));
            if ($alias === '') {
                continue;
            }

            DB::table('employee_aliases')->insert([
                'employee_default_id' => $e->id,
                'alias'               => $alias,
                'source'              => 'manual',
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_aliases');
    }
};
