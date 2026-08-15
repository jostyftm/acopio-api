<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\PersonStatus;
use App\Enums\RegistrationSource;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_type' => DocumentType::NationalId,
            'document_number' => fake()->unique()->numerify('##########'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '57'.fake()->numerify('3#########'),
            'municipality' => fake()->randomElement(['Buenaventura', 'Cali', 'Tumaco', 'Quibdó']),
            'neighborhood' => fake()->optional()->word(),
            'latitude' => fake()->optional()->latitude(1.0, 6.0),
            'longitude' => fake()->optional()->longitude(-78.0, -74.0),
            'status' => PersonStatus::Registered,
            'special_needs' => [],
            'source' => RegistrationSource::Web,
            'data_consent' => true,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PersonStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    public function located(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PersonStatus::Located,
            'verified_at' => now(),
            'located_at' => now(),
        ]);
    }

    public function viaSms(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => RegistrationSource::Sms,
        ]);
    }
}
