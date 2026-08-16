<?php

namespace Database\Factories;

use App\Enums\FacilityStatus;
use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'facility_type_id' => FacilityType::factory(),
            'name' => fake()->company(),
            'municipality_id' => null,
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'capacity' => fake()->numberBetween(10, 500),
            'available' => fn (array $attributes): int => fake()->numberBetween(0, (int) $attributes['capacity']),
            'description' => fake()->sentence(),
            'status' => FacilityStatus::Operational,
        ];
    }
}
