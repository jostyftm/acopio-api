<?php

use App\Models\Need;
use App\Models\Person;
use App\Models\SeverityNeed;

it('returns exists=false for an unknown document', function () {
    $this->getJson('/api/v1/registrations/check?document_type=CC&document_number=999999999')
        ->assertOk()
        ->assertJsonPath('data.exists', false);
});

it('returns exists=true without affectation for an existing person', function () {
    Person::factory()->create([
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
    ]);

    $this->getJson('/api/v1/registrations/check?document_type=CC&document_number=123456789')
        ->assertOk()
        ->assertJsonPath('data.exists', true)
        ->assertJsonPath('data.has_affectation', false)
        ->assertJsonPath('data.person.full_name', 'Maria Garcia')
        ->assertJsonPath('data.affectation', null);
});

it('returns exists=true with the affectation summary when the person has one', function () {
    $severities = derrumbesSeverities();

    $level = SeverityNeed::query()->firstOrCreate(['code_level' => 'medium'], ['display_name' => 'Medio']);
    $need = Need::query()->create([
        'name' => 'Comida',
        'normalized_name' => 'COMIDA',
        'severity_need_id' => $level->id,
    ]);
    $person = Person::factory()->create([
        'document_type' => 'CC',
        'document_number' => '123456789',
        'first_name' => 'Maria',
        'last_name' => 'Garcia',
    ]);
    $affectation = $person->affectation()->create([
        'incident_type_id' => $severities['incident_type_id'],
        'description' => 'Vivienda destruida',
    ]);
    $affectation->severities()->attach($severities['total_severity_id']);
    $affectation->needs()->attach($need);

    $this->getJson('/api/v1/registrations/check?document_type=CC&document_number=123456789')
        ->assertOk()
        ->assertJsonPath('data.exists', true)
        ->assertJsonPath('data.has_affectation', true)
        ->assertJsonPath('data.affectation.severities', ['total'])
        ->assertJsonPath('data.affectation.needs', ['Comida']);
});

it('matches documents case-insensitively and by type', function () {
    Person::factory()->create([
        'document_type' => 'CE',
        'document_number' => 'ABC123',
        'first_name' => 'Luis',
        'last_name' => 'Perez',
    ]);

    $this->getJson('/api/v1/registrations/check?document_type=CE&document_number=abc123')
        ->assertOk()
        ->assertJsonPath('data.exists', true);

    $this->getJson('/api/v1/registrations/check?document_type=CC&document_number=abc123')
        ->assertOk()
        ->assertJsonPath('data.exists', false);
});

it('rejects missing or invalid parameters', function () {
    $this->getJson('/api/v1/registrations/check')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['document_type', 'document_number']);

    $this->getJson('/api/v1/registrations/check?document_type=XX&document_number=123')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('document_type');
});
