<?php

use App\Models\Person;
use Illuminate\Support\Carbon;

it('registers a person publicly', function () {
    $severities = derrumbesSeverities();

    $response = $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'birth_date' => '1995-06-15',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'neighborhood' => 'La Playita',
        'sector' => 'urban',
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
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
    $severities = derrumbesSeverities();

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
        'birth_date' => '1995-06-15',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'sector' => 'urban',
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
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
    $severities = derrumbesSeverities();

    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '123456',
        'municipality' => 'Buenaventura',
        'sector' => 'urban',
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'data_consent' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('phone');
});

it('accepts phone numbers with the +57 country code', function () {
    $severities = derrumbesSeverities();

    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'birth_date' => '1995-06-15',
        'phone' => '+573001234567',
        'municipality' => 'Buenaventura',
        'sector' => 'urban',
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'data_consent' => true,
    ])->assertCreated();
});

it('rejects spam submissions via the honeypot field', function () {
    $severities = derrumbesSeverities();

    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'birth_date' => '1995-06-15',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'sector' => 'urban',
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
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

it('requires a birth date for every new person', function () {
    $severities = derrumbesSeverities();

    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'sector' => 'urban',
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'data_consent' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('birth_date');
});

it('stores the birth date and computes the current age', function () {
    $severities = derrumbesSeverities();

    $this->postJson('/api/v1/registrations', [
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
        'birth_date' => '1995-06-15',
        'phone' => '3001234567',
        'municipality' => 'Buenaventura',
        'sector' => 'urban',
        'incident_type_id' => $severities['incident_type_id'],
        'severities' => [$severities['partial_severity_id']],
        'data_consent' => true,
    ])->assertCreated()
        ->assertJsonPath('data.attributes.birth_date', '1995-06-15')
        ->assertJsonPath('data.attributes.current_age', Carbon::parse('1995-06-15')->age);
});
