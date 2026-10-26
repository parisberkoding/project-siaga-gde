<?php

namespace Database\Seeders;

use App\Models\Approved_Request;
use App\Models\Permohonan;
use App\Models\Site;
use App\Models\SuratSpd;
use App\Models\Unit;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Database\Seeder;

/**
 * Seed unit bisnis awal + pemetaan site→unit.
 * Unit bersifat CRUD — superadmin dapat menambah/mengubah kemudian.
 *
 * Backfill unit_id pada data historis dilakukan di sini (bukan di migration)
 * karena sumber acuannya (sites.unit_id / users.unit_id) baru terisi setelah
 * seeder ini menjalankan pemetaan site→unit.
 */
class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'PUSAT',  'name' => 'Kantor Pusat', 'province' => 'DKI Jakarta', 'city' => 'Jakarta', 'description' => 'Unit pusat / korporat'],
            ['code' => 'PATUHA', 'name' => 'Unit Patuha',  'province' => 'Jawa Barat',  'city' => 'Bandung',  'description' => 'Unit Geodipa Patuha'],
            ['code' => 'DIENG',  'name' => 'Unit Dieng',   'province' => 'Jawa Tengah', 'city' => 'Wonosobo', 'description' => 'Unit Geodipa Dieng'],
        ];

        foreach ($units as $data) {
            $unit = Unit::updateOrCreate(['code' => $data['code']], $data);

            // `is_central` sengaja di luar $fillable (kolom ini memberi bypass
            // gate modul & data scoping), sehingga tidak bisa ikut di $data.
            // Harus di-set eksplisit: pada instalasi baru, migration berjalan
            // SEBELUM seeder ini, jadi baris PUSAT belum ada saat itu.
            $shouldBeCentral = $data['code'] === 'PUSAT';
            if ($unit->is_central !== $shouldBeCentral) {
                $unit->is_central = $shouldBeCentral;
                $unit->save();
            }
        }

        // Site bersifat CRUD — superadmin dapat menambah/mengubah lewat menu
        // "Site / Lokasi". Setiap site wajib terhubung ke satu unit.
        $sites = [
            ['code' => 'Pusat',     'name' => 'Kantor Pusat – Jakarta', 'type' => 'office',      'unit' => 'PUSAT'],
            ['code' => 'PMU',       'name' => 'Kantor Pusat – PMU',     'type' => 'office',      'unit' => 'PUSAT'],
            ['code' => 'Patuha',    'name' => 'Power Plant Patuha',     'type' => 'power_plant', 'unit' => 'PATUHA'],
            ['code' => 'PTH',       'name' => 'Patuha (PTH)',           'type' => 'power_plant', 'unit' => 'PATUHA'],
            ['code' => 'PTH-BC',    'name' => 'Basecamp Patuha',        'type' => 'basecamp',    'unit' => 'PATUHA'],
            ['code' => 'BCMP',      'name' => 'Basecamp Patuha',        'type' => 'basecamp',    'unit' => 'PATUHA'],
            ['code' => 'SUMUR-1',   'name' => 'Sumur Patuha 1',         'type' => 'well',        'unit' => 'PATUHA'],
            ['code' => 'Dieng',     'name' => 'Power Plant Dieng',      'type' => 'power_plant', 'unit' => 'DIENG'],
            ['code' => 'DIENG-BC',  'name' => 'Basecamp Dieng',         'type' => 'basecamp',    'unit' => 'DIENG'],
            ['code' => 'DIENG-RSV', 'name' => 'Reservoir Dieng',        'type' => 'reservoir',   'unit' => 'DIENG'],
        ];

        foreach ($sites as $data) {
            $unitId = Unit::where('code', $data['unit'])->value('id');
            Site::updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'type' => $data['type'], 'unit_id' => $unitId, 'is_active' => true]
            );
        }

        $this->backfillUnitIds();
    }

    /**
     * Isi unit_id pada data historis yang masih NULL.
     * Diturunkan dari relasi yang sudah tersedia setelah site→unit terpetakan.
     * Memakai Eloquent agar portabel antar driver DB (MySQL & SQLite test).
     */
    private function backfillUnitIds(): void
    {
        Permohonan::whereNull('unit_id')
            ->with(['vehicle.detail.site', 'requester'])
            ->chunkById(200, function ($rows) {
                foreach ($rows as $p) {
                    $unitId = $p->vehicle?->detail?->site?->unit_id ?? $p->requester?->unit_id;
                    if ($unitId) {
                        $p->update(['unit_id' => $unitId]);
                    }
                }
            });

        Approved_Request::whereNull('unit_id')
            ->with('permohonan')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $a) {
                    if ($a->permohonan?->unit_id) {
                        $a->update(['unit_id' => $a->permohonan->unit_id]);
                    }
                }
            });

        SuratSpd::whereNull('unit_id')
            ->with(['user', 'employeeDefault.defaultSite'])
            ->chunkById(200, function ($rows) {
                foreach ($rows as $sp) {
                    $unitId = $sp->user?->unit_id ?? $sp->employeeDefault?->defaultSite?->unit_id;
                    if ($unitId) {
                        $sp->update(['unit_id' => $unitId]);
                    }
                }
            });

        VehicleAndToolsConsumers::whereNull('unit_id')
            ->with('detail.site')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $v) {
                    if ($v->detail?->site?->unit_id) {
                        $v->update(['unit_id' => $v->detail->site->unit_id]);
                    }
                }
            });
    }
}
