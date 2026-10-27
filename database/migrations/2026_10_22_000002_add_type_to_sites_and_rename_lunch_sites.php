<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Site = lokasi operasional di bawah Unit (Power Plant, Basecamp, Kantor,
 * Reservoir, Sumur). Sebelumnya nama site mencampur kode dan nama (mis.
 * "Basecamp", "Patuha", "Dieng") sehingga sulit dibedakan dari unit.
 *
 * Migrasi ini:
 *   - menambah kolom `type` untuk klasifikasi lokasi;
 *   - merapikan nama site lama menjadi nama yang eksplisit.
 *
 * Rename hanya dilakukan bila nama masih sama dengan nilai lama, supaya
 * perubahan manual yang sudah dilakukan admin tidak tertimpa saat migrasi
 * dijalankan ulang di environment lain.
 */
return new class extends Migration
{
    /** @var array<string, array{old: string, new: string, type: string}> */
    private array $mapping = [
        'PMU'       => ['old' => 'PMU',            'new' => 'Kantor Pusat – PMU',      'type' => 'office'],
        'Pusat'     => ['old' => 'Kantor Pusat',   'new' => 'Kantor Pusat – Jakarta',  'type' => 'office'],
        'Patuha'    => ['old' => 'Patuha',         'new' => 'Power Plant Patuha',      'type' => 'power_plant'],
        'PTH'       => ['old' => 'Patuha',         'new' => 'Patuha (PTH)',            'type' => 'power_plant'],
        'PTH-BC'    => ['old' => 'Basecamp Patuha', 'new' => 'Basecamp Patuha',        'type' => 'basecamp'],
        'BCMP'      => ['old' => 'Basecamp',       'new' => 'Basecamp Patuha',         'type' => 'basecamp'],
        'SUMUR-1'   => ['old' => 'Sumur Patuha 1', 'new' => 'Sumur Patuha 1',          'type' => 'well'],
        'Dieng'     => ['old' => 'Dieng',          'new' => 'Power Plant Dieng',       'type' => 'power_plant'],
        'DIENG-BC'  => ['old' => 'Basecamp Dieng', 'new' => 'Basecamp Dieng',          'type' => 'basecamp'],
        'DIENG-RSV' => ['old' => 'Reservoir Dieng', 'new' => 'Reservoir Dieng',        'type' => 'reservoir'],
    ];

    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'type')) {
                $table->string('type', 30)->nullable()->after('name');
            }
        });

        foreach ($this->mapping as $code => $data) {
            $query = DB::table('sites')->where('code', $code);

            if (! $query->exists()) {
                continue;
            }

            $updates = ['type' => $data['type']];

            if ($query->where('name', $data['old'])->exists()) {
                $updates['name'] = $data['new'];
            }

            DB::table('sites')->where('code', $code)->update($updates);
        }
    }

    public function down(): void
    {
        foreach ($this->mapping as $code => $data) {
            DB::table('sites')
                ->where('code', $code)
                ->where('name', $data['new'])
                ->update(['name' => $data['old']]);
        }

        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
