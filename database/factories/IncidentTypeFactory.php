<?php

namespace Database\Factories;

use App\Enums\SeverityMode;
use App\Models\IncidentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentType>
 */
class IncidentTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'incident-'.fake()->unique()->numberBetween(1, 999999),
            'display_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'severity_mode' => SeverityMode::Single,
            'is_active' => true,
        ];
    }
}
