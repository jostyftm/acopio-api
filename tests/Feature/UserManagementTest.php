<?php

use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

it('creates a user with the requested role as an admin', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($admin);

    $this->postJson('/api/v1/users', [
        'name' => 'New Operator',
        'email' => 'operator@acopio.test',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'operator',
    ])->assertCreated()->assertJsonPath('data.attributes.roles.0', 'operator');

    $user = User::where('email', 'operator@acopio.test')->first();
    expect($user)->not->toBeNull();
    expect($user->hasRole('operator', 'api'))->toBeTrue();
});

it('rejects duplicate emails when creating users', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($admin);

    $this->postJson('/api/v1/users', [
        'name' => 'Duplicate',
        'email' => $admin->email,
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'viewer',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('lists people for an authenticated viewer', function () {
    $this->seed(RolePermissionSeeder::class);
    Person::factory()->count(3)->create();
    $viewer = User::factory()->create();
    $viewer->assignRole(Role::findByName('viewer', 'api'));
    Passport::actingAs($viewer);

    $this->getJson('/api/v1/people')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('allows an organization admin to create staff in their own organization', function () {
    $this->seed(RolePermissionSeeder::class);
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole(Role::findByName('org_admin', 'api'));
    Passport::actingAs($orgAdmin);

    $this->postJson('/api/v1/users', [
        'name' => 'Staff Member',
        'email' => 'staff@fundacion.test',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'operator',
        'organization_id' => $organization->id,
    ])->assertCreated()
        ->assertJsonPath('data.attributes.roles.0', 'operator')
        ->assertJsonPath('data.attributes.organization.id', $organization->id);

    $user = User::where('email', 'staff@fundacion.test')->first();
    expect($user->organization_id)->toBe($organization->id);
});

it('blocks an organization admin from assigning elevated roles', function () {
    $this->seed(RolePermissionSeeder::class);
    $organization = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $organization->id]);
    $orgAdmin->assignRole(Role::findByName('org_admin', 'api'));
    Passport::actingAs($orgAdmin);

    $this->postJson('/api/v1/users', [
        'name' => 'Wannabe',
        'email' => 'wannabe@fundacion.test',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'admin',
        'organization_id' => $organization->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('role');
});

it('blocks an organization admin from creating users for another organization', function () {
    $this->seed(RolePermissionSeeder::class);
    $own = Organization::factory()->create();
    $other = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $own->id]);
    $orgAdmin->assignRole(Role::findByName('org_admin', 'api'));
    Passport::actingAs($orgAdmin);

    $this->postJson('/api/v1/users', [
        'name' => 'Intruder',
        'email' => 'intruder@fundacion.test',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'operator',
        'organization_id' => $other->id,
    ])->assertStatus(403);
});

it('allows an admin to create an organization admin', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($admin);

    $organization = Organization::factory()->create();

    $this->postJson('/api/v1/users', [
        'name' => 'Org Admin',
        'email' => 'org.admin@fundacion.test',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'org_admin',
        'organization_id' => $organization->id,
    ])->assertCreated()
        ->assertJsonPath('data.attributes.roles.0', 'org_admin');
});

it('scopes user listings to the organization for organization admins', function () {
    $this->seed(RolePermissionSeeder::class);
    $own = Organization::factory()->create();
    $other = Organization::factory()->create();

    $orgAdmin = User::factory()->create(['organization_id' => $own->id]);
    $orgAdmin->assignRole(Role::findByName('org_admin', 'api'));
    Passport::actingAs($orgAdmin);

    User::factory()->create(['organization_id' => $own->id]);
    User::factory()->create(['organization_id' => $other->id]);
    User::factory()->create();

    $this->getJson('/api/v1/users')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('blocks an organization admin from updating users of another organization', function () {
    $this->seed(RolePermissionSeeder::class);
    $own = Organization::factory()->create();
    $other = Organization::factory()->create();
    $orgAdmin = User::factory()->create(['organization_id' => $own->id]);
    $orgAdmin->assignRole(Role::findByName('org_admin', 'api'));
    Passport::actingAs($orgAdmin);

    $otherMember = User::factory()->create(['organization_id' => $other->id]);

    $this->putJson("/api/v1/users/{$otherMember->id}", ['name' => 'Hack'])
        ->assertStatus(403);
});
