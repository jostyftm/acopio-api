<?php

use App\Models\Person;
use App\Models\SearchReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

function makeUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findByName($role, 'api'));

    return $user;
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('rejects unauthenticated access to protected endpoints', function () {
    $this->getJson('/api/v1/me')->assertStatus(401);
    $this->getJson('/api/v1/stats')->assertStatus(401);
    $this->getJson('/api/v1/people')->assertStatus(401);
    $this->getJson('/api/v1/search-reports')->assertStatus(401);
    $this->getJson('/api/v1/users')->assertStatus(401);
});

it('returns the authenticated user from the me endpoint', function () {
    $user = makeUserWithRole('admin');
    Passport::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.attributes.email', $user->email)
        ->assertJsonPath('data.attributes.roles.0', 'admin');
});

it('allows operators to verify people but not delete them', function () {
    $person = Person::factory()->create();
    $operator = makeUserWithRole('operator');
    Passport::actingAs($operator);

    $this->postJson("/api/v1/people/{$person->id}/verify")
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'verified');

    $this->deleteJson("/api/v1/people/{$person->id}")
        ->assertStatus(403);

    expect($person->fresh())->not->toBeNull();
});

it('allows operators to mark a search report as found', function () {
    $person = Person::factory()->create();
    $report = SearchReport::factory()->create();
    $operator = makeUserWithRole('operator');
    Passport::actingAs($operator);

    $this->patchJson("/api/v1/search-reports/{$report->id}", [
        'status' => 'found',
        'person_id' => $person->id,
    ])->assertOk()->assertJsonPath('data.attributes.status', 'found');

    expect($person->fresh()->status->value)->toBe('located');
});

it('blocks viewers from verifying people', function () {
    $person = Person::factory()->create();
    $viewer = makeUserWithRole('viewer');
    Passport::actingAs($viewer);

    $this->postJson("/api/v1/people/{$person->id}/verify")->assertStatus(403);
});

it('allows admins to delete people', function () {
    $person = Person::factory()->create();
    $admin = makeUserWithRole('admin');
    Passport::actingAs($admin);

    $this->deleteJson("/api/v1/people/{$person->id}")
        ->assertNoContent();

    expect(Person::find($person->id))->toBeNull();
});

it('blocks operators from managing users', function () {
    $operator = makeUserWithRole('operator');
    Passport::actingAs($operator);

    $this->postJson('/api/v1/users', [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
        'role' => 'viewer',
    ])->assertStatus(403);
});
