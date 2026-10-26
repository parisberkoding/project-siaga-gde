<?php

namespace Database\Factories;

use App\Models\Permohonan;
use App\Models\User;
use App\Models\VehicleAndToolsConsumers;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermohonanFactory extends Factory
{
    protected $model = Permohonan::class;

    public function definition(): array
    {
        return [
            'request_number' => $this->faker->unique()->numerify('REQ-####'),
            'requester_id' => User::factory(),
            'request_date' => now(),
            'gasoline_type' => 'Pertamax',
            'bill_amounts' => 300000,
            'status' => 'approved',
            'requester_notes' => null,
            'authorizer_id' => null,
            'authorization_date' => null,
            'authorizer_notes' => null,
            'consumerial_tools_id' => VehicleAndToolsConsumers::factory(),
        ];
    }
}
