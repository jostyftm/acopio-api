<?php

use App\Models\Affectation;
use App\Models\AffectationStatus;
use App\Models\IncidentType;
use App\Models\Need;
use App\Models\Organization;
use App\Models\Person;
use App\Models\PropertyType;
use App\Models\SeverityNeed;
use App\Models\User;
use Database\Seeders\AffectationStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(AffectationStatusSeeder::class);
    $this->propertyTypeId = PropertyType::factory()->create()->id;
});

it('allows a viewer to report an incident without a person', function () {
    Storage::fake('s3');

    $severities = derrumbesSeverities();
    $viewer = makeUserWithRole('viewer');
    Passport::actingAs($viewer);

    $this->postJson('/api/v1/affectations/report', [
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'property_types' => [$this->propertyTypeId],
        'address' => 'Calle 5 # 12-34',
        'description' => 'Techo destruido por el deslizamiento',
        'evidence' => [UploadedFile::fake()->image('reporte.jpg')],
    ])->assertCreated()
        ->assertJsonPath('data.attributes.person_id', null)
        ->assertJsonPath('data.attributes.status.code', 'reported')
        ->assertJsonPath('data.attributes.address', 'Calle 5 # 12-34')
        ->assertJsonPath('data.attributes.description', 'Techo destruido por el deslizamiento')
        ->assertJsonPath('data.attributes.reported_by', $viewer->id)
        ->assertJsonPath('data.attributes.severities.0.code', 'partial');

    $affectation = Affectation::query()->firstOrFail();

    expect($affectation->person_id)->toBeNull()
        ->and($affectation->reported_by)->toBe($viewer->id)
        ->and($affectation->status->code)->toBe('reported')
        ->and($affectation->attachments)->toHaveCount(1)
        ->and($affectation->attachments->first()->original_name)->toBe('reporte.jpg')
        ->and(Person::count())->toBe(0);

    Storage::disk('s3')->assertExists($affectation->attachments->first()->file_path);
});

it('accepts an incident GPS location instead of an address', function () {
    $severities = derrumbesSeverities();
    $viewer = makeUserWithRole('viewer');
    Passport::actingAs($viewer);

    $this->postJson('/api/v1/affectations/report', [
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'property_types' => [$this->propertyTypeId],
        'incident_latitude' => 6.2,
        'incident_longitude' => -75.6,
    ])->assertCreated();

    $affectation = Affectation::query()->firstOrFail();

    expect($affectation->latitude)->toBe(6.2)
        ->and($affectation->longitude)->toBe(-75.6)
        ->and($affectation->address)->toBeNull();
});

it('requires an address or an incident GPS location', function () {
    $severities = derrumbesSeverities();
    $viewer = makeUserWithRole('viewer');
    Passport::actingAs($viewer);

    $this->postJson('/api/v1/affectations/report', [
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'property_types' => [$this->propertyTypeId],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['address', 'incident_latitude']);
});

it('only accepts needs tied to the incident type', function () {
    $severities = derrumbesSeverities();
    $viewer = makeUserWithRole('viewer');
    Passport::actingAs($viewer);

    $level = SeverityNeed::query()->firstOrCreate(['code_level' => 'medium'], ['display_name' => 'Medio']);
    $need = Need::query()->create([
        'name' => 'Comida',
        'normalized_name' => 'COMIDA',
        'severity_need_id' => $level->id,
    ]);

    IncidentType::query()->findOrFail($severities['incident_type_id'])->needs()->attach($need);

    $this->postJson('/api/v1/affectations/report', [
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'property_types' => [$this->propertyTypeId],
        'incident_latitude' => 6.2,
        'incident_longitude' => -75.6,
        'needs' => [$need->id],
    ])->assertCreated()
        ->assertJsonPath('data.attributes.needs.0.id', $need->id);

    $this->postJson('/api/v1/affectations/report', [
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'property_types' => [$this->propertyTypeId],
        'incident_latitude' => 6.2,
        'incident_longitude' => -75.6,
        'needs' => [Need::query()->create([
            'name' => 'Transporte',
            'normalized_name' => 'TRANSPORTE',
            'severity_need_id' => $level->id,
        ])->id],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('needs.0');
});

it('rejects a report without the affectations.report permission', function () {
    $severities = derrumbesSeverities();
    $user = User::factory()->create();
    Passport::actingAs($user);

    $this->postJson('/api/v1/affectations/report', [
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'property_types' => [$this->propertyTypeId],
        'incident_latitude' => 6.2,
        'incident_longitude' => -75.6,
    ])->assertStatus(403);
});

it('allows an operator to verify and locate a reported affectation', function () {
    $severities = derrumbesSeverities();
    $organization = Organization::factory()->create();
    $operator = makeUserWithRole('operator');
    $operator->update(['organization_id' => $organization->id]);
    Passport::actingAs($operator);

    $affectation = Affectation::query()->create([
        'incident_type_id' => $severities['incident_type_id'],
        'reported_by' => $operator->id,
        'organization_id' => $organization->id,
        'status_id' => AffectationStatus::query()->where('code', 'reported')->value('id'),
    ]);
    $affectation->severities()->attach($severities['partial_severity_id']);

    $this->postJson("/api/v1/affectations/{$affectation->id}/verify")
        ->assertOk()
        ->assertJsonPath('data.attributes.status.code', 'verified')
        ->assertJsonPath('data.attributes.verified_at', fn ($value) => $value !== null)
        ->assertJsonPath('data.attributes.located_at', null);

    expect($affectation->fresh()->verified_by)->toBe($operator->id);

    $this->postJson("/api/v1/affectations/{$affectation->id}/verify", [
        'latitude' => 6.2,
        'longitude' => -75.6,
    ])->assertOk()
        ->assertJsonPath('data.attributes.status.code', 'located')
        ->assertJsonPath('data.attributes.latitude', 6.2)
        ->assertJsonPath('data.attributes.longitude', -75.6)
        ->assertJsonPath('data.attributes.located_at', fn ($value) => $value !== null);
});

it('rejects verification for a viewer and for reports outside the organization', function () {
    $severities = derrumbesSeverities();

    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();

    $reporter = makeUserWithRole('operator');
    $reporter->update(['organization_id' => $otherOrganization->id]);

    $affectation = Affectation::query()->create([
        'incident_type_id' => $severities['incident_type_id'],
        'reported_by' => $reporter->id,
        'organization_id' => $otherOrganization->id,
        'status_id' => AffectationStatus::query()->where('code', 'reported')->value('id'),
    ]);

    $viewer = makeUserWithRole('viewer');
    Passport::actingAs($viewer);

    $this->postJson("/api/v1/affectations/{$affectation->id}/verify")->assertStatus(403);

    $operator = makeUserWithRole('operator');
    $operator->update(['organization_id' => $organization->id]);
    Passport::actingAs($operator);

    $this->postJson("/api/v1/affectations/{$affectation->id}/verify")->assertStatus(403);
});
