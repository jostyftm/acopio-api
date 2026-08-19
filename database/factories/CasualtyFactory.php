<?php

namespace Database\Factories;

use App\Enums\CasualtyType;
use App\Models\Affectation;
use App\Models\Casualty;
use App\Models\CasualtyCause;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Casualty>
 */
class CasualtyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'affectation_id' => Affectation::factory(),
            'person_id' => Person::factory(),
            'type' => CasualtyType::Injured,
            'cause_id' => null,
        ];
    }

    public function deceased(?CasualtyCause $cause = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CasualtyType::Deceased,
            'cause_id' => $cause?->id ?? CasualtyCause::query()->first()?->id,
        ]);
    }

    public function injured(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CasualtyType::Injured,
            'cause_id' => null,
        ]);
    }
}
