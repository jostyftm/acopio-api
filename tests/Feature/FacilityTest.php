<?php

use App\Models\Facility;
use App\Models\FacilityType;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makeFacilityAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));

    return $admin;
}

function makeOrganizationAdminFor(Organization $organization): User
{
    $admin = User::factory()->create(['organization_id' => $organization->id]);
    $admin->assignRole(Role::findByName('org_admin', 'api'));

    return $admin;
}

function facilityPayload(Organization $organization, FacilityType $type, array $overrides = []): array
{
    return array_merge([
        'organization_id' => $organization->id,
        'facility_type_id' => $type->id,
        'name' => 'Albergue Central',
        'status' => 'operational',
    ], $overrides);
}

it('allows an admin to create a facility', function () {
    Passport::actingAs(makeFacilityAdmin());

    $organization = Organization::factory()->create();
    $type = FacilityType::factory()->create();

    $this->postJson('/api/v1/facilities', facilityPayload($organization, $type))
        ->assertCreated()
        ->assertJsonPath('data.attributes.name', 'Albergue Central')
        ->assertJsonPath('data.attributes.facility_type.id', $type->id);
});

it('allows an organization admin to create a facility in their own organization', function () {
    $organization = Organization::factory()->create();
    Passport::actingAs(makeOrganizationAdminFor($organization));

    $type = FacilityType::factory()->create();

    $this->postJson('/api/v1/facilities', facilityPayload($organization, $type))
        ->assertCreated();
});

it('rejects an organization admin creating a facility for another organization', function () {
    $own = Organization::factory()->create();
    $other = Organization::factory()->create();
    Passport::actingAs(makeOrganizationAdminFor($own));

    $type = FacilityType::factory()->create();

    $this->postJson('/api/v1/facilities', facilityPayload($other, $type))
        ->assertStatus(403);
});

it('rejects an operator without the facilities permission', function () {
    $organization = Organization::factory()->create();
    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'api'));
    Passport::actingAs($operator);

    $type = FacilityType::factory()->create();

    $this->postJson('/api/v1/facilities', facilityPayload($organization, $type))
        ->assertStatus(403);
});

it('validates facility type must exist and be active', function () {
    Passport::actingAs(makeFacilityAdmin());

    $organization = Organization::factory()->create();
    $type = FacilityType::factory()->create();

    $this->postJson('/api/v1/facilities', facilityPayload($organization, $type, [
        'facility_type_id' => 9999,
    ]))->assertUnprocessable()->assertJsonValidationErrors('facility_type_id');
});

it('scopes facility listings to the organization for organization admins', function () {
    $own = Organization::factory()->create();
    $other = Organization::factory()->create();
    $admin = makeOrganizationAdminFor($own);
    Passport::actingAs($admin);

    $type = FacilityType::factory()->create();
    $this->postJson('/api/v1/facilities', facilityPayload($own, $type))->assertCreated();
    $this->postJson('/api/v1/facilities', facilityPayload($other, $type))->assertStatus(403);

    $this->getJson('/api/v1/facilities')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('blocks an organization admin from updating another organization facility', function () {
    $own = Organization::factory()->create();
    $other = Organization::factory()->create();
    $admin = makeOrganizationAdminFor($own);
    Passport::actingAs($admin);

    $type = FacilityType::factory()->create();
    $otherFacility = Facility::factory()->create([
        'organization_id' => $other->id,
        'facility_type_id' => $type->id,
    ]);

    $this->putJson("/api/v1/facilities/{$otherFacility->id}", ['name' => 'Hack'])
        ->assertStatus(403);
});

it('allows an admin to update and delete any facility', function () {
    Passport::actingAs(makeFacilityAdmin());

    $type = FacilityType::factory()->create();
    $facility = Facility::factory()->create(['facility_type_id' => $type->id]);

    $this->putJson("/api/v1/facilities/{$facility->id}", [
        'status' => 'full',
        'available' => 0,
    ])->assertOk()->assertJsonPath('data.attributes.status', 'full');

    $this->deleteJson("/api/v1/facilities/{$facility->id}")->assertNoContent();
});
