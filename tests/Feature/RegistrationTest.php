<?php

use App\Models\Person;

it('registers a person publicly', function () {
    $response = $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'neighborhood' => 'La Playita',
        'data_consent' => true,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'person')
        ->assertJsonPath('meta.duplicate', false)
        ->assertJsonPath('data.attributes.document_number', '123456789');

    $this->assertDatabaseHas('people', [
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'source' => 'web',
    ]);
});

it('returns the existing record when the document is already registered', function () {
    Person::factory()->create([
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
    ]);

    $response = $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'data_consent' => true,
    ]);

    $response->assertOk()->assertJsonPath('meta.duplicate', true);

    expect(Person::count())->toBe(1);
});

it('requires data consent', function () {
    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
    ])->assertUnprocessable()->assertJsonValidationErrors('data_consent');
});

it('rejects invalid phone numbers', function () {
    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '123456',
        'municipality' => 'Buenaventura',
        'data_consent' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('phone');
});

it('accepts phone numbers with the +57 country code', function () {
    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '+573001234567',
        'municipality' => 'Buenaventura',
        'data_consent' => true,
    ])->assertCreated();
});

it('rejects spam submissions via the honeypot field', function () {
    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'data_consent' => true,
        'website' => 'http://spam.example',
    ])->assertUnprocessable()->assertJsonValidationErrors('website');
});

it('returns a validation envelope with 422 status', function () {
    $this->postJson('/api/v1/registrations', [])
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'message' => __('messages.validation'),
        ])
        ->assertJsonStructure(['errors' => ['document_type', 'first_name']]);
});
