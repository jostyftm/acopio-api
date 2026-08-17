<?php

use App\Models\Department;
use App\Models\Municipality;
use App\Models\Organization;
use App\Models\OrganizationType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeOrganizationAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));

    return $admin;
}

it('allows an admin to create an organization', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $type = OrganizationType::factory()->create();
    $department = Department::query()->create([
        'code' => '76',
        'name' => 'Valle del Cauca',
        'normalized_name' => 'VALLE DEL CAUCA',
    ]);
    $municipality = Municipality::query()->create([
        'department_id' => $department->id,
        'code' => '76001',
        'name' => 'Cali',
        'normalized_name' => 'CALI',
        'boundary' => 'MULTIPOLYGON(((-76.6 3.4, -76.5 3.4, -76.5 3.5, -76.6 3.4)))',
    ]);

    $this->postJson('/api/v1/organizations', [
        'organization_type_id' => $type->id,
        'name' => 'Fundación Esperanza',
        'nit' => '901123456-0',
        'municipality_id' => $municipality->id,
        'status' => 'active',
    ])->assertCreated()
        ->assertJsonPath('data.attributes.name', 'Fundación Esperanza')
        ->assertJsonPath('data.attributes.organization_type.id', $type->id)
        ->assertJsonPath('data.attributes.municipality_id', $municipality->id)
        ->assertJsonPath('data.attributes.municipality.name', 'Cali');
});

it('rejects an organization admin from creating organizations', function () {
    $orgAdmin = User::factory()->create();
    $orgAdmin->assignRole(Role::findByName('org_admin', 'api'));
    Passport::actingAs($orgAdmin);

    $type = OrganizationType::factory()->create();

    $this->postJson('/api/v1/organizations', [
        'organization_type_id' => $type->id,
        'name' => 'Fundación Esperanza',
        'status' => 'active',
    ])->assertStatus(403);
});

it('lists organizations for an admin', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $type = OrganizationType::factory()->create();
    $this->postJson('/api/v1/organizations', [
        'organization_type_id' => $type->id,
        'name' => 'Fundación A',
        'status' => 'active',
    ])->assertCreated();

    $this->getJson('/api/v1/organizations')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('validates a unique nit when creating organizations', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $type = OrganizationType::factory()->create();

    $this->postJson('/api/v1/organizations', [
        'organization_type_id' => $type->id,
        'name' => 'Fundación A',
        'nit' => '901123456-0',
        'status' => 'active',
    ])->assertCreated();

    $this->postJson('/api/v1/organizations', [
        'organization_type_id' => $type->id,
        'name' => 'Fundación B',
        'nit' => '901123456-0',
        'status' => 'active',
    ])->assertUnprocessable()->assertJsonValidationErrors('nit');
});

it('validates the organization type must exist and be active', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $this->postJson('/api/v1/organizations', [
        'organization_type_id' => 9999,
        'name' => 'Fundación X',
        'status' => 'active',
    ])->assertUnprocessable()->assertJsonValidationErrors('organization_type_id');
});

it('validates the municipality must exist when provided', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $type = OrganizationType::factory()->create();

    $this->postJson('/api/v1/organizations', [
        'organization_type_id' => $type->id,
        'name' => 'Fundación X',
        'municipality_id' => 9999,
        'status' => 'active',
    ])->assertUnprocessable()->assertJsonValidationErrors('municipality_id');
});

it('allows an admin to update an organization', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $type = OrganizationType::factory()->create();
    $organization = Organization::factory()->create(['organization_type_id' => $type->id]);

    $this->putJson("/api/v1/organizations/{$organization->id}", [
        'name' => 'Fundación Renombrada',
        'status' => 'inactive',
    ])->assertOk()
        ->assertJsonPath('data.attributes.name', 'Fundación Renombrada')
        ->assertJsonPath('data.attributes.status', 'inactive');
});

it('allows an admin to set the municipality of an organization', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $type = OrganizationType::factory()->create();
    $organization = Organization::factory()->create(['organization_type_id' => $type->id]);

    $department = Department::query()->create([
        'code' => '76',
        'name' => 'Valle del Cauca',
        'normalized_name' => 'VALLE DEL CAUCA',
    ]);
    $municipality = Municipality::query()->create([
        'department_id' => $department->id,
        'code' => '76001',
        'name' => 'Cali',
        'normalized_name' => 'CALI',
        'boundary' => 'MULTIPOLYGON(((-76.6 3.4, -76.5 3.4, -76.5 3.5, -76.6 3.4)))',
    ]);

    $this->putJson("/api/v1/organizations/{$organization->id}", [
        'municipality_id' => $municipality->id,
    ])->assertOk()
        ->assertJsonPath('data.attributes.municipality_id', $municipality->id)
        ->assertJsonPath('data.attributes.municipality.name', 'Cali');
});

it('allows an admin to delete an organization', function () {
    Passport::actingAs(makeOrganizationAdmin());

    $type = OrganizationType::factory()->create();
    $organization = Organization::factory()->create(['organization_type_id' => $type->id]);

    $this->deleteJson("/api/v1/organizations/{$organization->id}")->assertNoContent();

    expect(Organization::find($organization->id))->toBeNull();
});
