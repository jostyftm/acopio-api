<?php

use App\Models\Affectation;
use App\Models\Municipality;
use App\Models\Need;
use App\Models\Person;
use App\Models\SearchReport;
use App\Models\SeverityNeed;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('municipalities:import', [
        '--file' => base_path('tests/Fixtures/geo/municipalities-sample.geojson'),
        '--no-interaction' => true,
    ]);
});

it('returns aggregated statistics for authenticated users', function () {
    $this->seed(RolePermissionSeeder::class);
    $severities = derrumbesSeverities();
    $cali = Municipality::where('code', '76001')->firstOrFail()->id;
    $medellin = Municipality::where('code', '05001')->firstOrFail()->id;

    Person::factory()->count(3)->create(['municipality_id' => $cali, 'location' => null]);
    Person::factory()->located()->create([
        'municipality_id' => $cali,
        'location' => Point::makeGeodetic(3.42, -76.52),
        'document_number' => '123456789',
    ]);
    Person::factory()->verified()->create([
        'municipality_id' => $medellin,
        'location' => null,
        'document_number' => '987654321',
    ]);
    SearchReport::factory()->create(['status' => 'pending', 'person_id' => null]);

    $high = SeverityNeed::query()->firstOrCreate(['code_level' => 'high'], ['display_name' => 'Alto']);
    $low = SeverityNeed::query()->firstOrCreate(['code_level' => 'low'], ['display_name' => 'Bajo']);

    $shelter = Need::query()->create([
        'name' => 'Albergue',
        'normalized_name' => 'ALBERGUE',
        'severity_need_id' => $high->id,
    ]);
    $children = Need::query()->create([
        'name' => 'Niños',
        'normalized_name' => 'NINOS',
        'severity_need_id' => $low->id,
    ]);

    $partial = Affectation::query()->create([
        'person_id' => Person::where('document_number', '123456789')->firstOrFail()->id,
        'incident_type_id' => $severities['incident_type_id'],
    ]);
    $partial->severities()->attach($severities['partial_severity_id']);
    $partial->needs()->attach($shelter);

    $total = Affectation::query()->create([
        'person_id' => Person::where('document_number', '987654321')->firstOrFail()->id,
        'incident_type_id' => $severities['incident_type_id'],
    ]);
    $total->severities()->attach($severities['total_severity_id']);
    $total->needs()->attach($children);

    $user = User::factory()->create();
    $user->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($user);

    $response = $this->getJson('/api/v1/stats')
        ->assertOk()
        ->assertJsonPath('data.total_people', 5)
        ->assertJsonPath('data.by_status.registered', 3)
        ->assertJsonPath('data.by_status.verified', 1)
        ->assertJsonPath('data.by_status.located', 1)
        ->assertJsonPath('data.by_municipality.Cali', 4)
        ->assertJsonPath('data.by_municipality.Medellín', 1)
        ->assertJsonPath('data.pending_search_reports', 1)
        ->assertJsonPath('data.people_with_location', 1)
        ->assertJsonPath('data.total_affected_people', 2)
        ->assertJsonPath('data.by_affectation_severity.partial', 1)
        ->assertJsonPath('data.by_affectation_severity.total', 1)
        ->assertJsonPath('data.by_property_type', [])
        ->assertJsonPath('data.by_incident_type.0.display_name', 'Derrumbes')
        ->assertJsonPath('data.by_incident_type.0.total', 2)
        ->assertJsonPath('data.needs_by_impact_level.high', 1)
        ->assertJsonPath('data.needs_by_impact_level.medium', 0)
        ->assertJsonPath('data.needs_by_impact_level.low', 1);

    $topNeeds = collect($response->json('data.top_needs'))->pluck('name');

    expect($topNeeds)->toContain('Albergue')
        ->toContain('Niños')
        ->and($response->json('data.top_needs'))->toHaveCount(2);
});
