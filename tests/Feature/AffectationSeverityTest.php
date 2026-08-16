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

function makeSeverityAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));

    return $admin;
}

function severityPayload(int $incidentTypeId, array $overrides = []): array
{
    return array_merge([
        'incident_type_id' => $incidentTypeId,
        'code' => 'partial',
        'display_name' => 'Afectación parcial',
        'description' => 'Daños parciales en la vivienda',
        'order' => 1,
        'is_active' => true,
    ], $overrides);
}

it('allows an admin to create a severity for an incident type', function () {
    Passport::actingAs(makeSeverityAdmin());

    $type = IncidentType::factory()->create();

    $this->postJson('/api/v1/affectation-severities', severityPayload($type->id))
        ->assertCreated()
        ->assertJsonPath('data.code', 'partial')
        ->assertJsonPath('data.incident_type_id', $type->id);
});

it('allows the same severity code in different incident types', function () {
    Passport::actingAs(makeSeverityAdmin());

    $a = IncidentType::factory()->create();
    $b = IncidentType::factory()->create();

    $this->postJson('/api/v1/affectation-severities', severityPayload($a->id))->assertCreated();
    $this->postJson('/api/v1/affectation-severities', severityPayload($b->id))->assertCreated();
});

it('rejects a duplicate severity code within the same incident type', function () {
    Passport::actingAs(makeSeverityAdmin());

    $type = IncidentType::factory()->create();
    AffectationSeverity::factory()->create(['incident_type_id' => $type->id, 'code' => 'partial']);

    $this->postJson('/api/v1/affectation-severities', severityPayload($type->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('validates that the incident type exists', function () {
    Passport::actingAs(makeSeverityAdmin());

    $this->postJson('/api/v1/affectation-severities', severityPayload(999999))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('incident_type_id');
});

it('rejects creating a severity without permission', function () {
    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'api'));
    Passport::actingAs($operator);

    $type = IncidentType::factory()->create();

    $this->postJson('/api/v1/affectation-severities', severityPayload($type->id))->assertForbidden();
});

it('allows an admin to update and delete a severity', function () {
    Passport::actingAs(makeSeverityAdmin());

    $type = IncidentType::factory()->create();
    $severity = AffectationSeverity::factory()->create(['incident_type_id' => $type->id]);

    $this->putJson("/api/v1/affectation-severities/{$severity->id}", [
        'display_name' => 'Afectación parcial leve',
        'order' => 2,
    ])->assertOk()
        ->assertJsonPath('data.display_name', 'Afectación parcial leve')
        ->assertJsonPath('data.order', 2);

    $this->deleteJson("/api/v1/affectation-severities/{$severity->id}")->assertNoContent();

    expect(AffectationSeverity::find($severity->id))->toBeNull();
});

it('rejects deleting a severity assigned to affectations', function () {
    Passport::actingAs(makeSeverityAdmin());

    $severities = derrumbesSeverities();
    makeAffectation();

    $this->deleteJson("/api/v1/affectation-severities/{$severities['partial_severity_id']}")
        ->assertStatus(409)
        ->assertJsonPath('success', false);
});

it('requires authentication to manage severities', function () {
    $this->postJson('/api/v1/affectation-severities', severityPayload(1))->assertStatus(401);
});
