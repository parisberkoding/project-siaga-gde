<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleAndToolsConsumersFactory extends Factory
{
    protected $model = \App\Models\VehicleAndToolsConsumers::class;

    public function definition(): array
    {
        return [
            'consumerial_type' => 'KENDARAAN',
            'consumerial_code' => 'B ' . $this->faker->randomNumber(4) . ' ' . strtoupper($this->faker->lexify('???')),
            'consumerial_name' => $this->faker->word,
            'consumerial_notes' => null,
            'optimum_jarak_per_liter' => null,
            'satuan' => 'km',
            'is_approved' => true,
            'is_active' => true,
            'approved_by' => null,
            'created_by' => User::factory(),
        ];
    }
}
