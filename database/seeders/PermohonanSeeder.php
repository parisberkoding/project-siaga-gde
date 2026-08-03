<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permohonan;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Carbon\Carbon;

class PermohonanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get requester IDs (role = requester)
        $requesters = User::where('role', 'requester')->pluck('id')->toArray();

        // Get manager ID for authorizer (role = manager)
        $manager = User::where('role', 'manager')->first();
        $managerId = $manager ? $manager->id : null;

        // ✅ Get vehicle IDs yang sudah approved
        $vehicleIds = VehicleAndToolsConsumers::where('is_approved', true)
            ->pluck('id')
            ->toArray();

        // Kalau belum ada vehicle, abort
        if (empty($vehicleIds)) {
            $this->command->error('❌ No approved vehicles found. Please run VTCSeeder first!');
            return;
        }

        $gasoline_types = ['Pertamax', 'Pertamina Dex'];
        $statuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];

        // Sample notes untuk requester
        $requester_notes_samples = [
            'Perjalanan ke site A untuk inspeksi rutin',
            'Monitoring sumur eksplorasi',
            'Kunjungan ke kantor cabang',
            'Pengambilan sampel di lapangan',
            'Meeting dengan vendor',
            null, // some without notes
            'Perjalanan dinas luar kota',
            'Survei lokasi baru',
            null,
            'Pengiriman dokumen penting',
        ];

        // Sample notes untuk authorizer
        $authorizer_notes_samples = [
            'Disetujui sesuai prosedur',
            'Urgent, segera proses',
            null,
            'Perjalanan sudah terjadwal',
            null,
            'Ditolak: tidak ada jadwal perjalanan',
            'Approved untuk kegiatan operasional',
            null,
            'Budget sudah dialokasikan',
            null,
        ];

        $permohonans = [];

        for ($i = 1; $i <= 20; $i++) {
            // Random requester
            $requesterId = $requesters[array_rand($requesters)];

            // ✅ Random vehicle dari yang sudah approved
            $vehicleId = $vehicleIds[array_rand($vehicleIds)];

            // Random gasoline type
            $gasolineType = $gasoline_types[array_rand($gasoline_types)];

            // Random amount (200k - 800k, kelipatan 50k)
            $billAmount = rand(4, 16) * 50000; // 200k, 250k, 300k, ..., 800k

            // Random status dengan distribusi yang masuk akal
            $randomStatusIndex = rand(1, 100);
            if ($randomStatusIndex <= 35) {
                $status = 'completed'; // 35%
            } elseif ($randomStatusIndex <= 65) {
                $status = 'approved'; // 30%
            } elseif ($randomStatusIndex <= 85) {
                $status = 'pending'; // 20%
            } elseif ($randomStatusIndex <= 95) {
                $status = 'rejected'; // 10%
            } else {
                $status = 'cancelled'; // 5%
            }

            // Request date (random dalam 30 hari terakhir)
            $requestDate = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

            // Generate request number berdasarkan tanggal
            $requestNumber = 'REQ' . $requestDate->format('dmY') . strtoupper(substr(md5(uniqid()), 0, 6));

            // Authorization details (hanya untuk yang bukan pending)
            $authorizerId = null;
            $authorizationDate = null;
            $authorizerNotes = null;

            if ($status !== 'pending') {
                $authorizerId = $managerId;
                // Authorization date setelah request date (beberapa jam sampai beberapa hari kemudian)
                $authorizationDate = (clone $requestDate)->addHours(rand(1, 72));
                $authorizerNotes = $authorizer_notes_samples[array_rand($authorizer_notes_samples)];
            }

            // Requester notes
            $requesterNotes = $requester_notes_samples[array_rand($requester_notes_samples)];

            $permohonans[] = [
                'request_number' => $requestNumber,
                'requester_id' => $requesterId,
                'request_date' => $requestDate,
                'gasoline_type' => $gasolineType,
                'bill_amounts' => $billAmount,
                'status' => $status,
                'requester_notes' => $requesterNotes,
                'authorizer_id' => $authorizerId,
                'authorization_date' => $authorizationDate,
                'authorizer_notes' => $authorizerNotes,
                'consumerial_tools_id' => $vehicleId, // ✅ Random vehicle ID
                'created_at' => $requestDate,
                'updated_at' => $authorizationDate ?? $requestDate,
            ];
        }

        // Insert semua data
        foreach ($permohonans as $permohonan) {
            Permohonan::create($permohonan);
        }

        $this->command->info('✅ Created ' . count($permohonans) . ' sample permohonan with random vehicles');
    }
}
