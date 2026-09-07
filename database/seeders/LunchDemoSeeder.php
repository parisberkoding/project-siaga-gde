<?php

namespace Database\Seeders;

use App\Models\EmployeeDefault;
use App\Models\EmployeeAlias;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LunchDemoSeeder extends Seeder
{
    public function run(): void
    {
        $sites = Site::pluck('id', 'code');
        $users = User::pluck('id', 'username');

        $employees = [
            [
                'employee_code' => 'EMP001',
                'employee_name' => 'Ahmad Fauzi',
                'division'      => 'Operasi',
                'position'      => 'Operator',
                'default_site_id' => $sites['Patuha'] ?? 2,
            ],
            [
                'employee_code' => 'EMP002',
                'employee_name' => 'Budi Hartono',
                'division'      => 'Pemeliharaan',
                'position'      => 'Teknisi',
                'default_site_id' => $sites['Patuha'] ?? 2,
            ],
            [
                'employee_code' => 'EMP003',
                'employee_name' => 'Citra Dewi',
                'division'      => 'Administrasi',
                'position'      => 'Staff Admin',
                'default_site_id' => $sites['PMU'] ?? 1,
            ],
            [
                'employee_code' => 'EMP004',
                'employee_name' => 'Deni Pratama',
                'division'      => 'Operasi',
                'position'      => 'Driver',
                'default_site_id' => $sites['Patuha'] ?? 2,
            ],
            [
                'employee_code' => 'EMP005',
                'employee_name' => 'Eka Putri',
                'division'      => 'Keuangan',
                'position'      => 'Staff Keuangan',
                'default_site_id' => $sites['PMU'] ?? 1,
            ],
            [
                'employee_code' => 'EMP006',
                'employee_name' => 'Fajar Nugroho',
                'division'      => 'Operasi',
                'position'      => 'Operator',
                'default_site_id' => $sites['BCMP'] ?? 3,
            ],
            [
                'employee_code' => 'EMP007',
                'employee_name' => 'Genny Kusumaningrum',
                'division'      => 'HC, GA & Finance',
                'position'      => 'Manager',
                'default_site_id' => $sites['PMU'] ?? 1,
            ],
            [
                'employee_code' => 'EMP008',
                'employee_name' => 'Hadi Setiawan',
                'division'      => 'Pemeliharaan',
                'position'      => 'Supervisor',
                'default_site_id' => $sites['Patuha'] ?? 2,
            ],
            [
                'employee_code' => 'EMP009',
                'employee_name' => 'Indah Lestari',
                'division'      => 'Administrasi',
                'position'      => 'Staff Admin',
                'default_site_id' => $sites['PMU'] ?? 1,
            ],
            [
                'employee_code' => 'EMP010',
                'employee_name' => 'Joko Widodo',
                'division'      => 'Operasi',
                'position'      => 'Driver',
                'default_site_id' => $sites['Patuha'] ?? 2,
            ],
        ];

        $createdIds = [];
        foreach ($employees as $data) {
            $data['is_active'] = true;
            $data['slug'] = Str::slug($data['employee_name']);
            $emp = EmployeeDefault::firstOrCreate(
                ['employee_code' => $data['employee_code']],
                $data
            );
            $createdIds[] = $emp->id;
        }

        // Aliases
        $aliases = [
            ['employee_name' => 'Ahmad Fauzi', 'aliases' => ['ahmad', 'fauzi', 'ahmad fauzi', 'pak ahmad']],
            ['employee_name' => 'Budi Hartono', 'aliases' => ['budi', 'hartono', 'pak budi']],
            ['employee_name' => 'Citra Dewi',  'aliases' => ['citra', 'dewi']],
            ['employee_name' => 'Deni Pratama', 'aliases' => ['deni', 'pratama']],
            ['employee_name' => 'Fajar Nugroho','aliases' => ['fajar', 'nugroho']],
        ];

        foreach ($aliases as $item) {
            $emp = EmployeeDefault::where('employee_name', $item['employee_name'])->first();
            if (!$emp) continue;
            foreach ($item['aliases'] as $alias) {
                EmployeeAlias::firstOrCreate(
                    ['employee_default_id' => $emp->id, 'alias' => $alias],
                    ['source' => 'manual']
                );
            }
        }

        // Lunch forecasts for upcoming days (self-booking demo)
        $today = Carbon::today();
        $employees_fc = EmployeeDefault::active()->get();
        $siteIds = $sites->values()->toArray();

        for ($day = 1; $day <= 14; $day++) {
            $date = $today->copy()->addDays($day);
            foreach ($employees_fc->random(min(5, $employees_fc->count())) as $emp) {
                $siteId = $siteIds[array_rand($siteIds)];
                DB::table('lunch_forecasts')->updateOrInsert(
                    [
                        'employee_default_id' => $emp->id,
                        'lunch_date'          => $date->toDateString(),
                    ],
                    [
                        'user_id'           => $emp->user_id,
                        'predicted_site_id' => $siteId,
                        'prediction_source' => 'self_booking',
                        'confidence'        => 100.0,
                        'created_by'        => $users['superadmin'] ?? 1,
                        'updated_by'        => $users['superadmin'] ?? 1,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]
                );
            }
        }

        $this->command->info(sprintf('Seeded %d employees, %d aliases, and 14-day forecasts.', count($createdIds), count($aliases) * 3));
    }
}
