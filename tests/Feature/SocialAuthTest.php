<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;

it('creates a user on first google login and assigns the viewer role', function () {
    $this->seed(RolePermissionSeeder::class);

    $socialUser = new SocialUser;
    $socialUser->id = 'google-sub-123';
    $socialUser->name = 'Ana Torres';
    $socialUser->email = 'ana.torres@example.com';

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn(Mockery::mock(['user' => $socialUser]));

    $this->get('/auth/google/callback')->assertRedirect('http://localhost');

    $user = User::query()
        ->where('provider', 'google')
        ->where('provider_id', 'google-sub-123')
        ->first();

    expect($user)->not->toBeNull();
    expect($user->email)->toBe('ana.torres@example.com');
    expect($user->hasRole('viewer', 'api'))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

it('reuses the existing account on a second google login', function () {
    $this->seed(RolePermissionSeeder::class);

    $existing = User::factory()->create([
        'provider' => 'google',
        'provider_id' => 'google-sub-123',
        'email' => 'ana.torres@example.com',
        'password' => null,
    ]);

    $socialUser = new SocialUser;
    $socialUser->id = 'google-sub-123';
    $socialUser->name = 'Ana Torres';
    $socialUser->email = 'ana.torres@example.com';

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn(Mockery::mock(['user' => $socialUser]));

    $this->get('/auth/google/callback')->assertRedirect('http://localhost');

    expect(User::count())->toBe(1);
    $this->assertAuthenticatedAs($existing->fresh());
});

it('rejects unsupported oauth providers', function () {
    $this->get('/auth/facebook/redirect')->assertNotFound();
});
