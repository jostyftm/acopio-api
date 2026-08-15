<?php

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
