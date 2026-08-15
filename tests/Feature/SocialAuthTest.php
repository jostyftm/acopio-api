<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\ClientRepository;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient('Test API Tokens', 'users');
});

function mockSocialiteDriver(string $provider, ?SocialUser $socialUser = null): void
{
    $driver = Mockery::mock();
    $driver->shouldReceive('stateless')->andReturnSelf();

    if ($socialUser !== null) {
        $driver->shouldReceive('user')->andReturn($socialUser);
    } else {
        $redirect = Mockery::mock();
        $redirect->shouldReceive('getTargetUrl')->andReturn('https://accounts.google.com/o/oauth2/auth?x=1');
        $driver->shouldReceive('redirect')->andReturn($redirect);
    }

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

function makeSocialUser(string $id, string $name, string $email): SocialUser
{
    $socialUser = new SocialUser;
    $socialUser->id = $id;
    $socialUser->name = $name;
    $socialUser->email = $email;

    return $socialUser;
}

it('returns the provider authorization url', function () {
    mockSocialiteDriver('google');

    $this->getJson('/api/v1/auth/google/redirect')
        ->assertOk()
        ->assertJsonPath('data.url', 'https://accounts.google.com/o/oauth2/auth?x=1');
});

it('creates a user on first google login and redirects with an access token', function () {
    $this->seed(RolePermissionSeeder::class);

    mockSocialiteDriver('google', makeSocialUser('google-sub-123', 'Ana Torres', 'ana.torres@example.com'));

    $response = $this->get('/auth/google/callback');

    $user = User::query()
        ->where('provider', 'google')
        ->where('provider_id', 'google-sub-123')
        ->first();

    expect($user)->not->toBeNull();
    expect($user->email)->toBe('ana.torres@example.com');
    expect($user->hasRole('viewer', 'api'))->toBeTrue();
    expect($user->tokens()->first()?->name)->toBe('spa');

    $response->assertRedirect();

    $location = $response->headers->get('Location');
    expect($location)->toStartWith(config('app.frontend_url').'/auth/callback#access_token=');

    parse_str(parse_url($location, PHP_URL_FRAGMENT), $params);

    $this->withToken($params['access_token'])->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.attributes.email', 'ana.torres@example.com');

    $this->assertGuest('web');
});

it('reuses the existing account on a second google login', function () {
    $this->seed(RolePermissionSeeder::class);

    User::factory()->create([
        'provider' => 'google',
        'provider_id' => 'google-sub-123',
        'email' => 'ana.torres@example.com',
        'password' => null,
    ]);

    mockSocialiteDriver('google', makeSocialUser('google-sub-123', 'Ana Torres', 'ana.torres@example.com'));

    $this->get('/auth/google/callback');

    expect(User::count())->toBe(1);
    $this->assertGuest();
});

it('rejects unsupported oauth providers', function () {
    $this->getJson('/api/v1/auth/facebook/redirect')->assertNotFound();
    $this->get('/auth/facebook/callback')->assertNotFound();
});
