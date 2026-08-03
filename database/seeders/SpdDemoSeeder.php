<?php

namespace Database\Seeders;

use App\Models\SuratSpd;
use App\Models\SuratSpdDetail;
use App\Models\SuratSpdBudget;
use App\Models\LpjPenggantian;
use App\Models\LpjPenggantianDetail;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpdDemoSeeder extends Seeder
{
    public function run(): void
    {
        $requester = User::where('role', 'requester')->first()
            ?? User::first();
        $superadmin = User::where('role', 'superadmin')->first()
            ?? User::first();
        $vehicle = VehicleAndToolsConsumers::where('consumerial_type', 'KENDARAAN')
            ->where('is_approved', true)
            ->first()
            ?? VehicleAndToolsConsumers::first();

        if (!$requester || !$superadmin || !$vehicle) {
            $this->command->error('Seeder membutuhkan users & vehicles. Jalankan UserSeeder+VTCSeeder dulu.');
            return;
        }

        $now = Carbon::now();

        $spds = [
            // 1. Approved — perjalanan 3 hari
            [
                'user_id'           => $requester->id,
                'pengemudi_nama'    => $requester->nama_lengkap,
                'tempat_tujuan'     => 'Kantor Pusat Jakarta',
                'melayani_bidang'   => 'Operasi & Pemeliharaan',
                'jumlah_pemakai'    => 3,
                'vehicle_id'        => $vehicle->id,
                'tanggal_berangkat' => $now->copy()->subDays(10)->toDateString(),
                'tanggal_kembali'   => $now->copy()->subDays(8)->toDateString(),
                'status'            => 'approved',
                'approved_by'       => $superadmin->id,
                'approved_at'       => $now->copy()->subDays(11),
                'lpj_deadline'      => $now->copy()->subDays(5),
                'catatan'           => 'Perjalanan dinas ke Jakarta untuk koordinasi proyek.',
                'passengers'        => [$requester->nama_lengkap, 'Budi Santoso', 'Dewi Rahayu'],
                'budget_days'       => [
                    ['nominal' => 500000, 'catatan' => 'Transportasi'],
                    ['nominal' => 350000, 'catatan' => 'Akomodasi'],
                    ['nominal' => 500000, 'catatan' => 'Transportasi'],
                ],
            ],
            // 2. Approved — perjalanan 2 hari
            [
                'user_id'           => $requester->id,
                'pengemudi_nama'    => $requester->nama_lengkap,
                'tempat_tujuan'     => 'Bandung',
                'melayani_bidang'   => 'Meeting Vendor',
                'jumlah_pemakai'    => 2,
                'vehicle_id'        => $vehicle->id,
                'tanggal_berangkat' => $now->copy()->subDays(5)->toDateString(),
                'tanggal_kembali'   => $now->copy()->subDays(4)->toDateString(),
                'status'            => 'approved',
                'approved_by'       => $superadmin->id,
                'approved_at'       => $now->copy()->subDays(6),
                'lpj_deadline'      => $now->copy()->subDays(1),
                'catatan'           => 'Meeting dengan vendor alat berat.',
                'passengers'        => [$requester->nama_lengkap, 'Asep Suherman'],
                'budget_days'       => [
                    ['nominal' => 400000, 'catatan' => 'Transport + Makan'],
                    ['nominal' => 400000, 'catatan' => 'Transport + Makan'],
                ],
            ],
            // 3. Pending — baru diajukan
            [
                'user_id'           => $requester->id,
                'pengemudi_nama'    => $requester->nama_lengkap,
                'tempat_tujuan'     => 'Cirebon',
                'melayani_bidang'   => 'Inspeksi Lapangan',
                'jumlah_pemakai'    => 4,
                'vehicle_id'        => $vehicle->id,
                'tanggal_berangkat' => $now->copy()->addDays(3)->toDateString(),
                'tanggal_kembali'   => $now->copy()->addDays(5)->toDateString(),
                'status'            => 'pending',
                'approved_by'       => null,
                'approved_at'       => null,
                'lpj_deadline'      => null,
                'catatan'           => 'Inspeksi rutin sumur di wilayah Cirebon.',
                'passengers'        => [$requester->nama_lengkap, 'Dani Ramdani', 'Eko Prasetyo', 'Fitriani'],
                'budget_days'       => [
                    ['nominal' => 450000, 'catatan' => null],
                    ['nominal' => 450000, 'catatan' => null],
                    ['nominal' => 450000, 'catatan' => null],
                ],
            ],
            // 4. Rejected
            [
                'user_id'           => $requester->id,
                'pengemudi_nama'    => $requester->nama_lengkap,
                'tempat_tujuan'     => 'Semarang',
                'melayani_bidang'   => 'Pelatihan',
                'jumlah_pemakai'    => 1,
                'vehicle_id'        => $vehicle->id,
                'tanggal_berangkat' => $now->copy()->subDays(15)->toDateString(),
                'tanggal_kembali'   => $now->copy()->subDays(12)->toDateString(),
                'status'            => 'rejected',
                'approved_by'       => $superadmin->id,
                'approved_at'       => $now->copy()->subDays(16),
                'lpj_deadline'      => null,
                'catatan'           => 'Pelatihan K3.',
                'rejection_reason'  => 'Budget bulan ini sudah habis. Ajukan bulan depan.',
                'passengers'        => [$requester->nama_lengkap],
                'budget_days'       => [
                    ['nominal' => 400000, 'catatan' => 'Transport'],
                    ['nominal' => 400000, 'catatan' => 'Transport'],
                    ['nominal' => 400000, 'catatan' => 'Transport'],
                    ['nominal' => 400000, 'catatan' => 'Transport'],
                ],
            ],
        ];

        $suratIds = [];
        foreach ($spds as $data) {
            $budgetDays = $data['budget_days'];
            $passengers = $data['passengers'];
            unset($data['budget_days'], $data['passengers']);

            $data['pemberi_tugas_nama']    = 'Genny Kusumaningrum';
            $data['pemberi_tugas_jabatan'] = 'HC, GA, & Finance Manager';
            $data['total_budget'] = collect($budgetDays)->sum('nominal');

            if ($data['status'] === 'approved') {
                $data['tanggal_surat'] = $data['approved_at'] 
                    ? 'Patuha, ' . Carbon::parse($data['approved_at'])->translatedFormat('d F Y')
                    : 'Patuha, ' . $now->translatedFormat('d F Y');
            } else {
                $data['tanggal_surat'] = 'Patuha, ' . $now->translatedFormat('d F Y');
            }

            // Generate nomor surat manual (format model)
            $tahun = $now->format('Y');
            $bulanRomawi = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][(int)$now->format('n') - 1];
            $nomorUrut = SuratSpd::whereYear('created_at', $tahun)->max('nomor_urut') ?? 0;
            $nomorUrut++;
            $nomorSurat = sprintf('%03d.TGS/PTH1.30-GDE/%s/%s', $nomorUrut, $bulanRomawi, $tahun);

            // Lama perjalanan
            $lamaHari = Carbon::parse($data['tanggal_berangkat'])->diffInDays(Carbon::parse($data['tanggal_kembali'])) + 1;

            // Insert langsung via DB agar boot creating tidak overwrite total_budget
            $id = DB::table('surat_spds')->insertGetId(array_merge($data, [
                'nomor_surat'          => $nomorSurat,
                'nomor_urut'           => $nomorUrut,
                'lama_perjalanan_hari' => $lamaHari,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]));
            $suratIds[] = $id;

            // Passengers
            foreach ($passengers as $i => $nama) {
                SuratSpdDetail::create([
                    'surat_spd_id' => $id,
                    'nama_lengkap' => $nama,
                    'urutan'       => $i + 1,
                ]);
            }

            // Budgets
            foreach ($budgetDays as $j => $b) {
                $tanggal = Carbon::parse($data['tanggal_berangkat'])->addDays($j);
                SuratSpdBudget::create([
                    'surat_spd_id' => $id,
                    'tanggal'      => $tanggal,
                    'nominal'      => $b['nominal'],
                    'catatan'      => $b['catatan'] ?? null,
                ]);
            }
        }

        // LPJ untuk SPD #1 (approved, sudah lewat deadline)
        $spd1 = SuratSpd::find($suratIds[0]);
        if ($spd1) {
            $lpjNumber1 = LpjPenggantian::generateLpjNumber();
            $lpj1 = LpjPenggantian::create([
                'lpj_number'          => $lpjNumber1,
                'surat_spd_id'          => $spd1->id,
                'user_id'               => $requester->id,
                'nama'                  => $requester->nama_lengkap,
                'jabatan'               => $requester->jabatan ?? 'Staff',
                'vehicle_id'            => $vehicle->id,
                'nomor_polisi'          => $vehicle->consumerial_code,
                'uraian'                => 'Perjalanan dinas ke Jakarta 3 hari.',
                'total_biaya_tol'       => 150000,
                'total_biaya_portir'    => 50000,
                'total_biaya_bbm'       => 450000,
                'total_biaya_lain'      => 200000,
                'total_pengeluaran'     => 850000,
                'uang_dari'             => 'KAS',
                'jumlah_diberikan'      => 1350000,
                'pengembalian'          => 500000,
                'kekurangan'            => 0,
                'budget_per_hari'       => 450000,
                'is_over_budget'        => false,
                'status'                => 'approved',
                'approved_by_pengguna'  => $superadmin->id,
                'approved_pengguna_at'  => $now->copy()->subDays(4),
                'approved_by_divisi_ga' => $superadmin->id,
                'approved_divisi_ga_at' => $now->copy()->subDays(3),
                'tanggal_lpj'           => $now->copy()->subDays(4),
                'due_date'              => $now->copy()->subDays(5),
            ]);

            // LPJ details
            $lpjDetails = [
                ['tanggal' => $now->copy()->subDays(10), 'tipe_penggunaan' => 'Tol',     'nominal' => 150000, 'keterangan' => 'Tol Jakarta-Cikampek'],
                ['tanggal' => $now->copy()->subDays(10), 'tipe_penggunaan' => 'BBM',    'nominal' => 250000, 'keterangan' => 'Isi bensin SPBU Cikampek'],
                ['tanggal' => $now->copy()->subDays(9),  'tipe_penggunaan' => 'Parkir',  'nominal' => 50000,  'keterangan' => 'Parkir hotel'],
                ['tanggal' => $now->copy()->subDays(9),  'tipe_penggunaan' => 'BBM',    'nominal' => 200000, 'keterangan' => 'Isi bensin SPBU Jakarta'],
                ['tanggal' => $now->copy()->subDays(8),  'tipe_penggunaan' => 'Tol',     'nominal' => 150000, 'keterangan' => 'Tol Cikampek-Jakarta'],
                ['tanggal' => $now->copy()->subDays(8),  'tipe_penggunaan' => 'Lain-lain','nominal' => 50000,  'keterangan' => 'Parkir + makan'],
            ];

            foreach ($lpjDetails as $i => $d) {
                LpjPenggantianDetail::create([
                    'lpj_penggantian_id' => $lpj1->id,
                    'tanggal'            => $d['tanggal'],
                    'tipe_penggunaan'    => $d['tipe_penggunaan'],
                    'nominal'            => $d['nominal'],
                    'keterangan'         => $d['keterangan'],
                    'urutan'             => $i + 1,
                ]);
            }
        }

        // LPJ untuk SPD #2 (approved, pending LPJ — menunggu approval GA)
        $spd2 = SuratSpd::find($suratIds[1]);
        if ($spd2) {
            $lpjNumber2 = LpjPenggantian::generateLpjNumber();
            $lpj2 = LpjPenggantian::create([
                'lpj_number'          => $lpjNumber2,
                'surat_spd_id'        => $spd2->id,
                'user_id'             => $requester->id,
                'nama'                => $requester->nama_lengkap,
                'jabatan'             => $requester->jabatan ?? 'Staff',
                'vehicle_id'          => $vehicle->id,
                'nomor_polisi'        => $vehicle->consumerial_code,
                'uraian'              => 'Meeting vendor di Bandung 2 hari.',
                'total_biaya_tol'     => 80000,
                'total_biaya_portir'  => 0,
                'total_biaya_bbm'     => 300000,
                'total_biaya_lain'    => 100000,
                'total_pengeluaran'   => 480000,
                'uang_dari'           => 'Transfer',
                'jumlah_diberikan'    => 800000,
                'pengembalian'        => 320000,
                'kekurangan'          => 0,
                'budget_per_hari'     => 400000,
                'is_over_budget'      => false,
                'status'              => 'pending',
                'tanggal_lpj'         => $now->copy()->subDays(4),
                'due_date'            => $now->copy()->subDays(1),
            ]);

            $lpjDetails2 = [
                ['tanggal' => $now->copy()->subDays(5), 'tipe_penggunaan' => 'Tol',    'nominal' => 80000,  'keterangan' => 'Tol Pasteur'],
                ['tanggal' => $now->copy()->subDays(5), 'tipe_penggunaan' => 'BBM',   'nominal' => 200000, 'keterangan' => 'Isi bensin SPBU Pasteur'],
                ['tanggal' => $now->copy()->subDays(4), 'tipe_penggunaan' => 'Parkir', 'nominal' => 30000,  'keterangan' => 'Parkir hotel'],
                ['tanggal' => $now->copy()->subDays(4), 'tipe_penggunaan' => 'BBM',   'nominal' => 100000, 'keterangan' => 'Isi bensin perjalanan pulang'],
                ['tanggal' => $now->copy()->subDays(4), 'tipe_penggunaan' => 'Lain-lain','nominal' => 70000, 'keterangan' => 'Makan siang tim'],
            ];

            foreach ($lpjDetails2 as $i => $d) {
                LpjPenggantianDetail::create([
                    'lpj_penggantian_id' => $lpj2->id,
                    'tanggal'            => $d['tanggal'],
                    'tipe_penggunaan'    => $d['tipe_penggunaan'],
                    'nominal'            => $d['nominal'],
                    'keterangan'         => $d['keterangan'],
                    'urutan'             => $i + 1,
                ]);
            }
        }

        $this->command->info(sprintf('Seeded %d SPD, %d LPJ.', count($suratIds), 2));
    }
}
