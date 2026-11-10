<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'recorded_on' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'record_type' => fake()->randomElement(['Consultation', 'Laboratory result']),
            'provider_name' => fake()->company(),
            'title' => fake()->sentence(3),
            'summary' => fake()->paragraph(),
            'result_summary' => fake()->sentence(),
        ];
    }
}
