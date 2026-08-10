<?php

namespace Database\Factories;

use App\Models\SuratSpd;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuratSpdFactory extends Factory
{
    protected $model = SuratSpd::class;

    public function definition(): array
    {
        $berangkat = $this->faker->dateTimeBetween('-30 days', '-3 days');
        $kembali = (clone $berangkat)->modify('+3 days');

        return [
            'nomor_surat' => $this->faker->unique()->numerify('###.###.TGS/PTH1.30-GDE/###/2026'),
            'nomor_urut' => null,
            'pemberi_tugas_nama' => $this->faker->name,
            'pemberi_tugas_jabatan' => 'Supervisor General Affairs',
            'user_id' => User::factory(),
            'pengemudi_nama' => $this->faker->name,
            'tempat_tujuan' => $this->faker->city,
            'melayani_bidang' => $this->faker->word,
            'jumlah_pemakai' => 1,
            'vehicle_id' => VehicleAndToolsConsumers::factory(),
            'tanggal_berangkat' => $berangkat,
            'tanggal_kembali' => $kembali,
            'lama_perjalanan_hari' => 4,
            'total_budget' => 1400000,
            'parent_surat_id' => null,
            'is_extension' => false,
            'lpj_deadline' => (clone $kembali)->modify('+3 days'),
            'catatan' => null,
            'tanggal_surat' => 'Patuha, ' . now()->format('d F Y'),
            'type' => 'biasa',
            'status' => 'approved',
            'approved_by' => null,
            'approved_at' => now(),
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
        ];
    }
}
