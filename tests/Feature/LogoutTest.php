<?php

use App\Models\User;
use Laravel\Passport\ClientRepository;

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test API Tokens', 'users');
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('spa')->accessToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
});

it('requires an authenticated session to log out', function () {
    $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
});
