<?php

use App\Enums\AffectationSeverity;
use App\Models\Affectation;
use App\Models\Municipality;
use App\Models\Need;
use App\Models\Person;
use App\Models\SeverityNeed;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->artisan('municipalities:import', [
        '--file' => base_path('tests/Fixtures/geo/municipalities-sample.geojson'),
        '--no-interaction' => true,
    ]);
});

it('creates an affectation with needs and evidence when registering a person', function () {
    Storage::fake('s3');

    $level = SeverityNeed::query()->firstOrCreate(['code_level' => 'medium'], ['display_name' => 'Medio']);
    $need = Need::query()->create([
        'name' => 'Comida',
        'normalized_name' => 'COMIDA',
        'severity_need_id' => $level->id,
    ]);

    $this->post('/api/v1/registrations', [
        ...baseRegistrationPayload(),
        'severity' => 'partial',
        'description' => 'Techo destruido',
        'needs' => [$need->id],
        'evidence' => [UploadedFile::fake()->image('danos.jpg')],
    ])->assertCreated();

    $person = Person::where('document_number', '123456789')->firstOrFail();
    $affectation = $person->affectation()->firstOrFail();

    expect($affectation->severity)->toBe(AffectationSeverity::Partial)
        ->and($affectation->description)->toBe('Techo destruido')
        ->and($affectation->needs->contains($need))->toBeTrue()
        ->and($affectation->evidence)->toHaveCount(1)
        ->and($affectation->evidence->first()->original_name)->toBe('danos.jpg')
        ->and($affectation->evidence->first()->mime)->toBe('image/jpeg');

    Storage::disk('s3')->assertExists($affectation->evidence->first()->file_path);
});

it('stores the incident location on the affectation', function () {
    $this->post('/api/v1/registrations', [
        ...baseRegistrationPayload(),
        'severity' => 'total',
        'incident_latitude' => 6.2,
        'incident_longitude' => -75.6,
    ])->assertCreated();

    $affectation = Person::where('document_number', '123456789')->firstOrFail()->affectation()->firstOrFail();

    expect($affectation->latitude)->toBe(6.2)
        ->and($affectation->longitude)->toBe(-75.6);
});

it('does not create an affectation for a duplicate document', function () {
    Person::factory()->create(['document_type' => 'CC', 'document_number' => '123456789']);

    $this->post('/api/v1/registrations', [
        ...baseRegistrationPayload(),
        'severity' => 'partial',
    ])->assertOk()->assertJsonPath('meta.duplicate', true);

    expect(Affectation::count())->toBe(0);
});

it('resolves the municipality by code when coordinates do not resolve', function () {
    $this->post('/api/v1/registrations', [
        ...baseRegistrationPayload(),
        'municipality' => 'Unknown',
        'municipality_code' => '76001',
        'latitude' => 4.7,
        'longitude' => -74.1,
        'severity' => 'partial',
    ])->assertCreated()
        ->assertJsonPath('data.attributes.municipality', 'Cali');

    $cali = Person::where('document_number', '123456789')->firstOrFail()->municipality_id;

    expect(Municipality::where('code', '76001')->firstOrFail()->id)->toBe($cali);
});

it('updates severity, needs and location via PUT', function () {
    $this->seed(RolePermissionSeeder::class);

    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'api'));
    Passport::actingAs($operator);

    $level = SeverityNeed::query()->firstOrCreate(['code_level' => 'medium'], ['display_name' => 'Medio']);
    $need = Need::query()->create([
        'name' => 'Comida',
        'normalized_name' => 'COMIDA',
        'severity_need_id' => $level->id,
    ]);
    $other = Need::query()->create([
        'name' => 'Ropa',
        'normalized_name' => 'ROPA',
        'severity_need_id' => $level->id,
    ]);
    $person = Person::factory()->create();
    $affectation = $person->affectation()->create(['severity' => 'partial']);
    $affectation->needs()->attach($need);

    Storage::fake('s3');

    $this->put("/api/v1/affectations/{$affectation->id}", [
        'severity' => 'total',
        'description' => 'Actualizado',
        'needs' => [$other->id],
        'latitude' => 6.2,
        'longitude' => -75.6,
        'evidence' => [UploadedFile::fake()->image('foto.jpg')],
    ])->assertOk()
        ->assertJsonPath('data.severity', 'total');

    $affectation->refresh();
    $affectation->load(['needs', 'evidence']);

    expect($affectation->severity)->toBe(AffectationSeverity::Total)
        ->and($affectation->description)->toBe('Actualizado')
        ->and($affectation->needs->pluck('id')->all())->toBe([$other->id])
        ->and($affectation->latitude)->toBe(6.2)
        ->and($affectation->longitude)->toBe(-75.6)
        ->and($affectation->evidence)->toHaveCount(1);

    Storage::disk('s3')->assertExists($affectation->evidence->first()->file_path);
});

it('rejects updating an affectation without permission', function () {
    $this->seed(RolePermissionSeeder::class);

    $viewer = User::factory()->create();
    $viewer->assignRole(Role::findByName('viewer', 'api'));
    Passport::actingAs($viewer);

    $affectation = Person::factory()->create()->affectation()->create(['severity' => 'partial']);

    $this->putJson("/api/v1/affectations/{$affectation->id}", ['severity' => 'total'])->assertForbidden();
});

/**
 * @return array<string, mixed>
 */
function baseRegistrationPayload(): array
{
    return [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '3001234567',
        'municipality' => 'Cali',
        'sector' => 'urban',
        'data_consent' => true,
    ];
}
