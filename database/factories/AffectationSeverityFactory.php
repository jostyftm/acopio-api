<?php

namespace Database\Factories;

use App\Models\AffectationSeverity;
use App\Models\IncidentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffectationSeverity>
 */
class AffectationSeverityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_type_id' => IncidentType::factory(),
            'code' => 'severity-'.fake()->unique()->numberBetween(1, 999999),
            'display_name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
