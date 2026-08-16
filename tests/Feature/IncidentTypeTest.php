<?php

use App\Models\AffectationSeverity;
use App\Models\IncidentType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeIncidentTypeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));

    return $admin;
}

function incidentTypePayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'inundaciones',
        'display_name' => 'Inundaciones',
        'description' => 'Afectación por inundación',
        'severity_mode' => 'single',
        'is_active' => true,
    ], $overrides);
}

it('lists active incident types publicly with their severities', function () {
    IncidentType::factory()->create(['code' => 'inundaciones', 'display_name' => 'Inundaciones']);
    IncidentType::factory()->create(['code' => 'inactivo', 'display_name' => 'Inactivo', 'is_active' => false]);

    $this->getJson('/api/v1/incident-types')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', 'derrumbes')
        ->assertJsonCount(2, 'data.0.severities');

    $this->getJson('/api/v1/incident-types?include_inactive=1')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('allows an admin to create an incident type with a severity mode', function () {
    Passport::actingAs(makeIncidentTypeAdmin());

    $this->postJson('/api/v1/incident-types', incidentTypePayload())
        ->assertCreated()
        ->assertJsonPath('data.code', 'inundaciones')
        ->assertJsonPath('data.severity_mode', 'single');

    $this->postJson('/api/v1/incident-types', incidentTypePayload([
        'code' => 'alteraciones',
        'display_name' => 'Alteración del orden público',
        'severity_mode' => 'multiple',
    ]))
        ->assertCreated()
        ->assertJsonPath('data.severity_mode', 'multiple');
});

it('shows an incident type with its severities', function () {
    Passport::actingAs(makeIncidentTypeAdmin());

    $type = IncidentType::factory()->create();
    AffectationSeverity::factory()->create(['incident_type_id' => $type->id, 'code' => 'partial', 'display_name' => 'Parcial']);

    $this->getJson("/api/v1/incident-types/{$type->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.severities')
        ->assertJsonPath('data.severities.0.code', 'partial');
});

it('rejects creating an incident type without permission', function () {
    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'api'));
    Passport::actingAs($operator);

    $this->postJson('/api/v1/incident-types', incidentTypePayload())->assertForbidden();
});

it('validates the incident type code, format and severity mode', function () {
    Passport::actingAs(makeIncidentTypeAdmin());

    $this->postJson('/api/v1/incident-types', incidentTypePayload(['code' => 'ConMayuscula']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    IncidentType::factory()->create(['code' => 'inundaciones']);
    $this->postJson('/api/v1/incident-types', incidentTypePayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    $this->postJson('/api/v1/incident-types', incidentTypePayload(['code' => 'otro', 'severity_mode' => 'unknown']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('severity_mode');
});

it('allows an admin to update an incident type', function () {
    Passport::actingAs(makeIncidentTypeAdmin());

    $type = IncidentType::factory()->create(['severity_mode' => 'single']);

    $this->putJson("/api/v1/incident-types/{$type->id}", [
        'display_name' => 'Derrumbes y deslizamientos',
        'severity_mode' => 'multiple',
    ])->assertOk()
        ->assertJsonPath('data.display_name', 'Derrumbes y deslizamientos')
        ->assertJsonPath('data.severity_mode', 'multiple');
});

it('allows an admin to delete an incident type without affectations', function () {
    Passport::actingAs(makeIncidentTypeAdmin());

    $type = IncidentType::factory()->create();

    $this->deleteJson("/api/v1/incident-types/{$type->id}")->assertNoContent();

    expect(IncidentType::find($type->id))->toBeNull();
});

it('rejects deleting an incident type that has affectations', function () {
    Passport::actingAs(makeIncidentTypeAdmin());

    $severities = derrumbesSeverities();
    $type = IncidentType::query()->findOrFail($severities['incident_type_id']);
    makeAffectation();

    $this->deleteJson("/api/v1/incident-types/{$type->id}")
        ->assertStatus(409)
        ->assertJsonPath('success', false);
});
