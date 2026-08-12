<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\Unit;
use Illuminate\Database\Seeder;

/**
 * Seed unit bisnis awal + pemetaan site→unit.
 * Unit bersifat CRUD — superadmin dapat menambah/mengubah kemudian.
 */
class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['code' => 'PUSAT',  'name' => 'Kantor Pusat', 'description' => 'Unit pusat / korporat'],
            ['code' => 'PATUHA', 'name' => 'Unit Patuha',  'description' => 'Unit Geodipa Patuha'],
            ['code' => 'DIENG',  'name' => 'Unit Dieng',   'description' => 'Unit Geodipa Dieng'],
        ];

        foreach ($units as $data) {
            Unit::updateOrCreate(['code' => $data['code']], $data);
        }

        // Site bersifat CRUD — superadmin dapat menambah/mengubah lewat menu
        // "Site / Lokasi". Setiap site wajib terhubung ke satu unit.
        $sites = [
            ['code' => 'Pusat',   'name' => 'Kantor Pusat', 'unit' => 'PUSAT'],
            ['code' => 'PMU',     'name' => 'PMU',          'unit' => 'PUSAT'],
            ['code' => 'PTH',     'name' => 'Patuha',       'unit' => 'PATUHA'],
            ['code' => 'PTH-BC',  'name' => 'Basecamp Patuha', 'unit' => 'PATUHA'],
            ['code' => 'SUMUR-1', 'name' => 'Sumur Patuha 1', 'unit' => 'PATUHA'],
            ['code' => 'Dieng',   'name' => 'Dieng',        'unit' => 'DIENG'],
        ];

        foreach ($sites as $data) {
            $unitId = Unit::where('code', $data['unit'])->value('id');
            Site::updateOrCreate(
                ['code' => $data['code']],
                ['name' => $data['name'], 'unit_id' => $unitId, 'is_active' => true]
            );
        }
    }
}
