<?php

use App\Models\User;
use Laravel\Passport\Passport;

it('updates the profile name and email', function () {
    $user = User::factory()->create([
        'name' => 'Ana Torres',
        'email' => 'ana.torres@example.com',
    ]);
    Passport::actingAs($user);

    $this->putJson('/api/v1/me', [
        'name' => 'Ana María Torres',
        'email' => 'ana.nueva@example.com',
    ])->assertOk()
        ->assertJsonPath('data.attributes.name', 'Ana María Torres')
        ->assertJsonPath('data.attributes.email', 'ana.nueva@example.com');

    expect($user->fresh()->email)->toBe('ana.nueva@example.com');
});

it('rejects a profile email that is already in use', function () {
    $user = User::factory()->create(['email' => 'first@example.com']);
    User::factory()->create(['email' => 'taken@example.com']);
    Passport::actingAs($user);

    $this->putJson('/api/v1/me', [
        'name' => 'Ana',
        'email' => 'taken@example.com',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('keeps the institutional email read-only for SSO accounts', function () {
    $user = User::factory()->create([
        'name' => 'Ana',
        'email' => 'ana@institucion.org',
        'provider' => 'google',
    ]);
    Passport::actingAs($user);

    $this->putJson('/api/v1/me', [
        'name' => 'Ana Torres',
        'email' => 'otro@correo.com',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('allows SSO accounts to update their name', function () {
    $user = User::factory()->create([
        'name' => 'Ana',
        'email' => 'ana@institucion.org',
        'provider' => 'google',
    ]);
    Passport::actingAs($user);

    $this->putJson('/api/v1/me', [
        'name' => 'Ana Torres',
        'email' => 'ana@institucion.org',
    ])->assertOk()->assertJsonPath('data.attributes.name', 'Ana Torres');
});

it('requires authentication to update the profile', function () {
    $this->putJson('/api/v1/me', ['name' => 'Ana', 'email' => 'a@b.com'])->assertUnauthorized();
});
