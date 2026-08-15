<?php

use App\Models\User;
use Laravel\Passport\Client;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

function testAuthorizeClient(): Client
{
    return Client::factory()->asPublic()->create([
        'name' => 'OAuth test',
        'redirect_uris' => ['http://localhost:3000/auth/callback'],
    ]);
}

it('redirects an unauthenticated visitor to the login page', function (): void {
    $client = testAuthorizeClient();

    $response = $this->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost:3000/auth/callback',
        'response_type' => 'code',
        'scope' => '',
        'state' => 'test-state',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
    ]));

    $response->assertRedirect(route('login'));
});

it('auto-approves first-party clients and issues an authorization code', function (): void {
    $client = testAuthorizeClient();

    $response = $this->actingAs($this->user, 'web')->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost:3000/auth/callback',
        'response_type' => 'code',
        'scope' => '',
        'state' => 'test-state',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
    ]));

    $response->assertRedirect();

    $location = $response->headers->get('Location');
    expect($location)
        ->toStartWith('http://localhost:3000/auth/callback?code=')
        ->and($location)->toContain('state=test-state');
});

it('does not show the approval page to first-party clients', function (): void {
    $client = testAuthorizeClient();

    $response = $this->actingAs($this->user, 'web')->get('/oauth/authorize?'.http_build_query([
        'client_id' => $client->id,
        'redirect_uri' => 'http://localhost:3000/auth/callback',
        'response_type' => 'code',
        'scope' => '',
        'state' => 'test-state',
        'code_challenge' => str_repeat('a', 43),
        'code_challenge_method' => 'S256',
    ]));

    $response->assertDontSee('Authorize');
    $response->assertDontSee('approve');
});
