<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\PersonStatus;
use App\Enums\RegistrationSource;
use App\Models\Person;
use Clickbar\Magellan\Data\Geometries\Point;
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
            'neighborhood' => fake()->optional()->word(),
            'address' => fake()->optional()->streetAddress(),
            'sector' => fake()->optional(0.8)->randomElement(['urban', 'rural']),
            'location' => fake()->boolean(70)
                ? Point::makeGeodetic(fake()->latitude(1.0, 6.0), fake()->longitude(-78.0, -74.0))
                : null,
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
            'location' => Point::makeGeodetic(fake()->latitude(1.0, 6.0), fake()->longitude(-78.0, -74.0)),
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
