<?php

use App\Models\Municipality;
use App\Models\Person;

beforeEach(function () {
    $this->artisan('municipalities:import', [
        '--file' => base_path('tests/Fixtures/geo/municipalities-sample.geojson'),
        '--no-interaction' => true,
    ]);
});

it('returns people with masked document numbers on the public search', function () {
    Person::factory()->create([
        'document_type' => 'CC',
        'document_number' => '1234567890',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'municipality_id' => Municipality::where('code', '76001')->firstOrFail()->id,
    ]);

    $this->getJson('/api/v1/people/search?municipality=Cali')
        ->assertOk()
        ->assertJsonPath('data.0.attributes.masked_document_number', '******7890')
        ->assertJsonMissingPath('data.0.attributes.document_number')
        ->assertJsonMissingPath('data.0.attributes.phone');
});

it('filters public search results by status and municipality', function () {
    Person::factory()->located()->count(2)->create([
        'municipality_id' => Municipality::where('code', '76001')->firstOrFail()->id,
    ]);
    Person::factory()->create([
        'municipality_id' => Municipality::where('code', '76001')->firstOrFail()->id,
    ]);

    $this->getJson('/api/v1/people/search?filter[status]=located&filter[municipality]=Cali')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});
it('searches by term', function () {
    Person::factory()->create([
        'document_number' => '1010101010',
        'first_name' => 'Camila',
        'last_name' => 'Torres',
    ]);

    $this->getJson('/api/v1/people/search?term=Camila')
        ->assertOk()
        ->assertJsonPath('data.0.attributes.full_name', 'Camila Torres');
});

it('keeps the search endpoint public', function () {
    Person::factory()->create();

    $this->getJson('/api/v1/people/search?term=x')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('rejects invalid document_type filters', function () {
    $this->getJson('/api/v1/people/search?document_type=INVALID')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('document_type');
});
