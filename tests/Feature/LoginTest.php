<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\ClientRepository;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test API Tokens', 'users');
});

it('logs in with valid credentials and returns a usable token', function () {
    $this->seed(RolePermissionSeeder::class);
    $user = User::factory()->create([
        'email' => 'admin@acopio.test',
        'password' => 'password',
    ]);
    $user->assignRole(Role::findByName('admin', 'api'));

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@acopio.test',
        'password' => 'password',
    ])->assertOk();

    $token = $response->json('data.token');
    expect($token)->not->toBeNull();
    expect($response->json('data.user.attributes.roles.0'))->toBe('admin');

    $this->withToken($token)->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.attributes.email', 'admin@acopio.test');
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'admin@acopio.test',
        'password' => 'password',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@acopio.test',
        'password' => 'wrong-password',
    ])->assertStatus(401)->assertJsonPath('success', false);
});

it('rejects login attempts for unknown emails', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@acopio.test',
        'password' => 'password',
    ])->assertStatus(401);
});

it('validates required login fields', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('rejects inactive accounts', function () {
    User::factory()->create([
        'email' => 'inactive@acopio.test',
        'password' => 'password',
        'is_active' => false,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'inactive@acopio.test',
        'password' => 'password',
    ])->assertStatus(403);
});
