<?php

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lists the roles with their permissions', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $this->getJson('/api/v1/roles')
        ->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.attributes.name', 'admin');
});

it('creates a role with permissions', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $permission = Permission::query()->where('name', 'people.view')->firstOrFail();

    $this->postJson('/api/v1/roles', [
        'name' => 'coordinator',
        'permissions' => [$permission->id],
    ])->assertCreated()
        ->assertJsonPath('data.attributes.name', 'coordinator')
        ->assertJsonPath('data.attributes.permissions.0.name', 'people.view');
});

it('updates a role and syncs its permissions', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $view = Permission::query()->where('name', 'people.view')->firstOrFail();
    $verify = Permission::query()->where('name', 'people.verify')->firstOrFail();

    $role = Role::query()->create(['name' => 'coordinator', 'guard_name' => 'api']);
    $role->syncPermissions([$view->id]);

    $this->putJson("/api/v1/roles/{$role->id}", [
        'name' => 'senior_coordinator',
        'permissions' => [$verify->id],
    ])->assertOk()
        ->assertJsonPath('data.attributes.name', 'senior_coordinator')
        ->assertJsonCount(1, 'data.attributes.permissions')
        ->assertJsonPath('data.attributes.permissions.0.name', 'people.verify');
});

it('deletes a role that is not in use', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $role = Role::query()->create(['name' => 'temporary', 'guard_name' => 'api']);

    $this->deleteJson("/api/v1/roles/{$role->id}")->assertNoContent();

    expect(Role::find($role->id))->toBeNull();
});

it('rejects deleting a role assigned to users', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $role = Role::query()->create(['name' => 'temporary', 'guard_name' => 'api']);
    User::factory()->create()->assignRole($role);

    $this->deleteJson("/api/v1/roles/{$role->id}")->assertStatus(422);
});

it('validates role creation', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $this->postJson('/api/v1/roles', ['name' => 'org_admin'])->assertUnprocessable()->assertJsonValidationErrors('name');

    $this->postJson('/api/v1/roles', [
        'name' => 'with spaces',
    ])->assertUnprocessable()->assertJsonValidationErrors('name');

    $this->postJson('/api/v1/roles', [
        'name' => 'invalid',
        'permissions' => [9999],
    ])->assertUnprocessable()->assertJsonValidationErrors('permissions.0');
});

it('rejects non admins from managing roles', function () {
    Passport::actingAs(makeUserWithRole('org_admin'));

    $this->getJson('/api/v1/roles')->assertStatus(403);
    $this->postJson('/api/v1/roles', ['name' => 'coordinator'])->assertStatus(403);
});
