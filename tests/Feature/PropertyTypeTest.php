<?php

use App\Models\PropertyType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makePropertyTypeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));

    return $admin;
}

function propertyTypePayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'vivienda',
        'display_name' => 'Vivienda',
        'description' => 'Vivienda familiar',
        'is_active' => true,
    ], $overrides);
}

it('lists active property types publicly', function () {
    PropertyType::factory()->create(['code' => 'vivienda', 'display_name' => 'Vivienda', 'is_active' => true]);
    PropertyType::factory()->create(['code' => 'hospital', 'display_name' => 'Hospital', 'is_active' => false]);

    $this->getJson('/api/v1/property-types')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'vivienda');

    $this->getJson('/api/v1/property-types?include_inactive=1')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('allows an admin to create a property type', function () {
    Passport::actingAs(makePropertyTypeAdmin());

    $this->postJson('/api/v1/property-types', propertyTypePayload())
        ->assertCreated()
        ->assertJsonPath('data.code', 'vivienda')
        ->assertJsonPath('data.display_name', 'Vivienda');
});

it('rejects creating a property type without permission', function () {
    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'api'));
    Passport::actingAs($operator);

    $this->postJson('/api/v1/property-types', propertyTypePayload())->assertForbidden();
});

it('validates the property type code format and uniqueness', function () {
    Passport::actingAs(makePropertyTypeAdmin());

    $this->postJson('/api/v1/property-types', propertyTypePayload(['code' => 'ConMayuscula']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    PropertyType::factory()->create(['code' => 'vivienda']);
    $this->postJson('/api/v1/property-types', propertyTypePayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

it('allows an admin to update a property type', function () {
    Passport::actingAs(makePropertyTypeAdmin());

    $type = PropertyType::factory()->create();

    $this->putJson("/api/v1/property-types/{$type->id}", [
        'display_name' => 'Vivienda rural',
        'is_active' => false,
    ])->assertOk()
        ->assertJsonPath('data.display_name', 'Vivienda rural')
        ->assertJsonPath('data.is_active', false);
});

it('allows an admin to delete a property type without affectations', function () {
    Passport::actingAs(makePropertyTypeAdmin());

    $type = PropertyType::factory()->create();

    $this->deleteJson("/api/v1/property-types/{$type->id}")->assertNoContent();

    expect(PropertyType::find($type->id))->toBeNull();
});

it('rejects deleting a property type that is assigned to affectations', function () {
    Passport::actingAs(makePropertyTypeAdmin());

    $type = PropertyType::factory()->create();
    makeAffectation()->propertyTypes()->attach($type->id);

    $this->deleteJson("/api/v1/property-types/{$type->id}")
        ->assertStatus(409)
        ->assertJsonPath('success', false);
});

it('requires authentication to manage property types', function () {
    $this->postJson('/api/v1/property-types', propertyTypePayload())->assertStatus(401);
    $this->getJson('/api/v1/property-types')->assertOk();
});
