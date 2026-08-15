<?php

use App\Models\Department;

beforeEach(function () {
    $this->artisan('municipalities:import', [
        '--file' => base_path('tests/Fixtures/geo/municipalities-sample.geojson'),
        '--no-interaction' => true,
    ]);
});

it('lists departments publicly', function () {
    $response = $this->getJson('/api/v1/departments')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    expect(Department::count())->toBe(3)
        ->and($response->json('data.0.code'))->toBe('05')
        ->and($response->json('data.0.name'))->toBe('Antioquia');
});

it('filters departments by term', function () {
    $this->getJson('/api/v1/departments?term=antio')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', '05');
});

it('exposes the centroid of each department', function () {
    $this->getJson('/api/v1/departments')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['code', 'name', 'normalized_name', 'centroid' => ['latitude', 'longitude']],
            ],
        ]);

    expect(Department::where('code', '05')->firstOrFail()->centroid)->not->toBeNull();
});
