<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\ReportStatus;
use App\Models\Person;
use App\Models\SearchReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchReport>
 */
class SearchReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'searched_name' => fake()->name(),
            'document_type' => DocumentType::NationalId,
            'document_number' => fake()->numerify('##########'),
            'municipality' => fake()->randomElement(['Buenaventura', 'Cali', 'Tumaco']),
            'reporter_name' => fake()->name(),
            'reporter_phone' => '57'.fake()->numerify('3#########'),
            'relationship' => fake()->randomElement(['Mother', 'Father', 'Spouse', 'Sibling']),
            'status' => ReportStatus::Pending,
        ];
    }
}
