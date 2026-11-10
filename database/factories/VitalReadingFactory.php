<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\VitalReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VitalReading>
 */
class VitalReadingFactory extends Factory
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
            'recorded_on' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
            'heart_rate' => fake()->numberBetween(62, 82),
            'temperature' => fake()->randomFloat(1, 36.3, 37.3),
            'systolic' => fake()->numberBetween(108, 128),
            'diastolic' => fake()->numberBetween(70, 84),
        ];
    }
}
