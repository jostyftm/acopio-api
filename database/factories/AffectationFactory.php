<?php

namespace Database\Factories;

use App\Models\Affectation;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Affectation>
 */
class AffectationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'description' => fake()->optional()->sentence(),
            'address' => fake()->optional()->streetAddress(),
        ];
    }
}
