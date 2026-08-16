<?php

use App\Models\Department;
use App\Models\Municipality;
use App\Models\Person;
use App\Models\User;
use Clickbar\Magellan\Data\Geometries\Point;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('municipalities:import', [
        '--file' => base_path('tests/Fixtures/geo/municipalities-sample.geojson'),
        '--no-interaction' => true,
    ]);
});

it('imports municipalities and departments from a geojson file', function () {
    expect(Municipality::count())->toBe(3)
        ->and(Department::count())->toBe(3);

    $medellin = Municipality::where('code', '05001')->with('department')->firstOrFail();

    expect($medellin->name)->toBe('MEDELLÍN')
        ->and($medellin->normalized_name)->toBe('MEDELLIN')
        ->and($medellin->boundary)->not->toBeNull()
        ->and($medellin->centroid)->not->toBeNull()
        ->and($medellin->department->code)->toBe('05')
        ->and($medellin->department->name)->toBe('ANTIOQUIA')
        ->and($medellin->department->normalized_name)->toBe('ANTIOQUIA')
        ->and($medellin->department->centroid)->not->toBeNull();

    $this->assertSame(
        'MULTIPOLYGON',
        DB::scalar('SELECT GeometryType(boundary) FROM municipalities WHERE code = ?', ['05001']),
    );

    expect(Schema::hasColumns('municipalities', ['dpto_code', 'dpto_name']))->toBeFalse();
});

it('finds the covering municipality for given coordinates', function () {
    expect(Municipality::findByLatLng(6.2, -75.6)?->code)->toBe('05001');
    expect(Municipality::findByLatLng(3.45, -76.5)?->code)->toBe('76001');
    expect(Municipality::findByLatLng(12.55, -81.75)?->code)->toBe('88001');
    expect(Municipality::findByLatLng(12.55, -81.45)?->code)->toBe('88001');
});

it('returns null for coordinates without a covering municipality', function () {
    expect(Municipality::findByLatLng(12.55, -81.6))->toBeNull();
    expect(Municipality::findByLatLng(4.7, -74.1))->toBeNull();
});

it('assigns the municipality when registering a person', function () {
    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '987654321',
        'first_name' => 'Ana',
        'last_name' => 'Lopez',
        'birth_date' => '1990-01-01',
        'phone' => '3001234567',
        'municipality' => 'Unknown',
        'neighborhood' => 'Centro',
        'latitude' => 3.45,
        'longitude' => -76.5,
        'sector' => 'urban',
        'severity' => 'partial',
        'data_consent' => true,
    ])->assertCreated()
        ->assertJsonPath('data.attributes.municipality', 'Cali');

    $cali = Municipality::where('code', '76001')->firstOrFail();
    $person = Person::where('document_number', '987654321')->firstOrFail();

    expect($person->municipality?->name)->toBe('CALI')
        ->and($person->municipality_id)->toBe($cali->id);
});

it('leaves municipality unassigned when coordinates and name do not resolve', function () {
    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '111222333',
        'first_name' => 'Luis',
        'last_name' => 'Perez',
        'birth_date' => '1990-01-01',
        'phone' => '3001234567',
        'municipality' => 'Narnia',
        'latitude' => 4.7,
        'longitude' => -74.1,
        'sector' => 'urban',
        'severity' => 'total',
        'data_consent' => true,
    ])->assertCreated()
        ->assertJsonPath('data.attributes.municipality', null);

    expect(Person::where('document_number', '111222333')->firstOrFail()->municipality_id)->toBeNull();
});

it('resolves the municipality_id from the declared name when coordinates do not resolve', function () {
    $cali = Municipality::where('code', '76001')->firstOrFail();

    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '555666777',
        'first_name' => 'Carla',
        'last_name' => 'Diaz',
        'birth_date' => '1990-01-01',
        'phone' => '3001234567',
        'municipality' => 'Cali',
        'latitude' => 4.7,
        'longitude' => -74.1,
        'sector' => 'rural',
        'severity' => 'partial',
        'data_consent' => true,
    ])->assertCreated()
        ->assertJsonPath('data.attributes.municipality', 'Cali');

    $person = Person::where('document_number', '555666777')->firstOrFail();

    expect($person->municipality_id)->toBe($cali->id);
});

it('backfills municipality data for people with a location', function () {
    $person = Person::factory()->create([
        'location' => Point::makeGeodetic(3.45, -76.5),
    ]);

    $this->artisan('municipalities:import', [
        '--file' => base_path('tests/Fixtures/geo/municipalities-sample.geojson'),
        '--backfill-people' => true,
        '--no-interaction' => true,
    ]);

    $cali = Municipality::where('code', '76001')->firstOrFail();
    $person->refresh();

    expect($person->municipality?->name)->toBe('CALI')
        ->and($person->municipality_id)->toBe($cali->id);
});

it('does not keep a municipality text column on people', function () {
    expect(Schema::hasColumn('people', 'municipality'))->toBeFalse();
});

it('assigns the municipality when locating a person', function () {
    $this->seed(RolePermissionSeeder::class);

    $person = Person::factory()->create();
    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'api'));
    Passport::actingAs($operator);

    $this->postJson("/api/v1/people/{$person->id}/verify", [
        'latitude' => 6.2,
        'longitude' => -75.6,
    ])->assertOk();

    $medellin = Municipality::where('code', '05001')->firstOrFail();
    $person->refresh();

    expect($person->status->value)->toBe('located')
        ->and($person->municipality?->name)->toBe('MEDELLÍN')
        ->and($person->municipality_id)->toBe($medellin->id);
});

it('lists municipalities filtered by term and department', function () {
    $this->getJson('/api/v1/municipalities?term=medellin')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', '05001')
        ->assertJsonPath('data.0.name', 'Medellín')
        ->assertJsonPath('data.0.department', 'Antioquia')
        ->assertJsonPath('data.0.dpto_code', '05')
        ->assertJsonStructure([
            'data' => [
                ['centroid' => ['latitude', 'longitude']],
            ],
        ]);

    $this->getJson('/api/v1/municipalities?term=san+andres')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', '88001');

    $this->getJson('/api/v1/municipalities?department=valle+del+cauca')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', '76001');

    $this->getJson('/api/v1/municipalities')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});
