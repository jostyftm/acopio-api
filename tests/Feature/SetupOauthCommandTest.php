<?php

use Laravel\Passport\Client;

function testSpaClientId(): string
{
    return '01a003b4-a6b4-7083-abcb-d73aadaa18a7';
}

it('creates the SPA and internal clients when no id is configured', function (): void {
    config(['services.acopio.spa_client_id' => null]);

    $this->artisan('acopio:setup-oauth')->assertSuccessful();

    $spa = Client::where('name', 'ACOPIO SPA')->first();
    $internal = Client::where('name', 'ACOPIO Internal Tests')->first();

    expect($spa)->not->toBeNull()
        ->and($spa->secret)->toBeNull()
        ->and($spa->grant_types)->toContain('authorization_code')
        ->and($spa->redirect_uris)->toBe(['http://localhost:3000/auth/callback'])
        ->and($internal)->not->toBeNull()
        ->and($internal->secret)->not->toBeNull()
        ->and($internal->grant_types)->toContain('password');
});

it('creates the SPA client with the configured id', function (): void {
    config(['services.acopio.spa_client_id' => testSpaClientId()]);

    $this->artisan('acopio:setup-oauth')->assertSuccessful();

    $spa = Client::find(testSpaClientId());

    expect($spa)->not->toBeNull()
        ->and($spa->name)->toBe('ACOPIO SPA')
        ->and($spa->secret)->toBeNull()
        ->and($spa->redirect_uris)->toBe(['http://localhost:3000/auth/callback']);
});

it('realigns an existing SPA client to the configured id', function (): void {
    Client::factory()->asPublic()->create([
        'name' => 'ACOPIO SPA',
        'redirect_uris' => ['http://localhost:3000/auth/callback'],
    ]);
    config(['services.acopio.spa_client_id' => testSpaClientId()]);

    $this->artisan('acopio:setup-oauth')->assertSuccessful();

    $spa = Client::where('name', 'ACOPIO SPA')->first();

    expect($spa->id)->toBe(testSpaClientId())
        ->and(Client::count())->toBe(2);
});

it('is idempotent when run twice with a configured id', function (): void {
    config(['services.acopio.spa_client_id' => testSpaClientId()]);

    $this->artisan('acopio:setup-oauth')->assertSuccessful();
    $this->artisan('acopio:setup-oauth')->assertSuccessful();

    expect(Client::where('name', 'ACOPIO SPA')->count())->toBe(1)
        ->and(Client::where('name', 'ACOPIO Internal Tests')->count())->toBe(1);
});
