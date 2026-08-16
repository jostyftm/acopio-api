<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test API Tokens', 'users');
});

it('changes the password with the correct current password', function () {
    $user = User::factory()->create(['password' => 'password']);
    Passport::actingAs($user);

    $this->postJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects the old password after the change', function () {
    $user = User::factory()->create(['password' => 'password']);
    Passport::actingAs($user);

    $this->postJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertStatus(401);
});

it('rejects a wrong current password', function () {
    $user = User::factory()->create(['password' => 'password']);
    Passport::actingAs($user);

    $this->postJson('/api/v1/me/password', [
        'current_password' => 'incorrect',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertStatus(422);
});

it('requires password confirmation', function () {
    $user = User::factory()->create(['password' => 'password']);
    Passport::actingAs($user);

    $this->postJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'different',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('blocks password changes for SSO accounts', function () {
    $user = User::factory()->create(['password' => 'password', 'provider' => 'google']);
    Passport::actingAs($user);

    $this->postJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertStatus(403);
});

it('revokes other sessions when the password changes', function () {
    $user = User::factory()->create(['password' => 'password']);
    $currentToken = $user->createToken('spa')->accessToken;
    $otherToken = $user->createToken('spa')->accessToken;

    $this->withToken($currentToken)->postJson('/api/v1/me/password', [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertOk();

    app('auth')->forgetGuards();

    $this->withToken($currentToken)->getJson('/api/v1/me')->assertOk();

    app('auth')->forgetGuards();

    $this->withToken($otherToken)->getJson('/api/v1/me')->assertUnauthorized();
});
