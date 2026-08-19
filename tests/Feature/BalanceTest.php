<?php

use App\Models\Affectation;
use App\Models\Casualty;
use App\Models\CasualtyCause;
use App\Models\FamilyMember;
use App\Models\Person;
use App\Models\PropertyType;
use App\Services\Balance\BalanceService;
use Database\Seeders\CasualtyCauseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;

it('exposes the balance general via a public endpoint', function () {
    $severities = derrumbesSeverities();
    $affectation = makeAffectation(['incident_type_id' => $severities['incident_type_id']]);
    $affectation->severities()->attach($severities['total_severity_id']);
    $affectation->familyMembers()->create([
        'person_id' => $affectation->person_id,
        'family_group' => 1,
        'is_householder' => true,
    ]);

    $this->getJson('/api/v1/balance-general')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'familias_afectadas',
                'viviendas_afectadas',
                'viviendas_destruidas',
                'viviendas_averiadas',
                'fallecidos' => ['total', 'por_causa'],
                'lesionados',
                'generated_at',
            ],
        ])
        ->assertJsonPath('data.viviendas_afectadas', Affectation::count())
        ->assertJsonPath('data.viviendas_destruidas', 1)
        ->assertJsonPath('data.familias_afectadas', FamilyMember::query()->select('affectation_id', 'family_group')->distinct()->get()->count());
});

it('computes the balance from real database aggregates', function () {
    $severities = derrumbesSeverities();

    $first = makeAffectation(['incident_type_id' => $severities['incident_type_id']]);
    $first->severities()->attach($severities['total_severity_id']);
    $first->familyMembers()->create(['person_id' => $first->person_id, 'family_group' => 1, 'is_householder' => true]);
    $first->familyMembers()->create(['person_id' => Person::factory()->create()->id, 'family_group' => 2, 'is_householder' => true]);

    $second = makeAffectation(['incident_type_id' => $severities['incident_type_id']]);
    $second->familyMembers()->create(['person_id' => $second->person_id, 'family_group' => 1, 'is_householder' => true]);

    $this->seed(CasualtyCauseSeeder::class);
    $cause = CasualtyCause::query()->where('code', 'aplastamiento')->first();
    Casualty::factory()->deceased($cause)->create(['affectation_id' => $first->id]);
    Casualty::factory()->deceased($cause)->create(['affectation_id' => $first->id]);
    Casualty::factory()->injured()->create(['affectation_id' => $second->id]);

    $summary = app(BalanceService::class)->summary();

    expect($summary['familias_afectadas'])->toBe(3)
        ->and($summary['viviendas_afectadas'])->toBe(2)
        ->and($summary['viviendas_destruidas'])->toBe(1)
        ->and($summary['viviendas_averiadas'])->toBe(2)
        ->and($summary['fallecidos']['total'])->toBe(2)
        ->and($summary['fallecidos']['por_causa'])->toHaveCount(1)
        ->and($summary['fallecidos']['por_causa'][0])->toMatchArray(['code' => 'aplastamiento', 'total' => 2])
        ->and($summary['lesionados'])->toBe(1);
});

it('registers a deceased casualty with a cause', function () {
    $this->seed(RolePermissionSeeder::class);
    Passport::actingAs(makeUserWithRole('admin'));

    $this->seed(CasualtyCauseSeeder::class);
    $affectation = makeAffectation();
    $cause = CasualtyCause::query()->where('code', 'aplastamiento')->first();

    $this->postJson("/api/v1/affectations/{$affectation->id}/casualties", [
        'type' => 'deceased',
        'cause_id' => $cause->id,
    ])->assertCreated()
        ->assertJsonPath('data.type', 'deceased')
        ->assertJsonPath('data.cause_id', $cause->id);

    expect(Casualty::query()->where('affectation_id', $affectation->id)->count())->toBe(1);
});

it('registers an injured casualty without a cause', function () {
    $this->seed(RolePermissionSeeder::class);
    Passport::actingAs(makeUserWithRole('admin'));

    $affectation = makeAffectation();

    $this->postJson("/api/v1/affectations/{$affectation->id}/casualties", [
        'type' => 'injured',
        'person_id' => Person::factory()->create()->id,
    ])->assertCreated()
        ->assertJsonPath('data.type', 'injured')
        ->assertJsonPath('data.cause_id', null);
});

it('requires a cause for deceased casualties', function () {
    $this->seed(RolePermissionSeeder::class);
    Passport::actingAs(makeUserWithRole('admin'));

    $affectation = makeAffectation();

    $this->postJson("/api/v1/affectations/{$affectation->id}/casualties", [
        'type' => 'deceased',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('cause_id');
});

it('rejects a cause for injured casualties', function () {
    $this->seed(RolePermissionSeeder::class);
    Passport::actingAs(makeUserWithRole('admin'));

    $this->seed(CasualtyCauseSeeder::class);
    $affectation = makeAffectation();
    $cause = CasualtyCause::query()->where('code', 'aplastamiento')->first();

    $this->postJson("/api/v1/affectations/{$affectation->id}/casualties", [
        'type' => 'injured',
        'cause_id' => $cause->id,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('cause_id');
});

it('rejects registering casualties without permission', function () {
    $this->seed(RolePermissionSeeder::class);
    Passport::actingAs(makeUserWithRole('viewer'));

    $affectation = makeAffectation();

    $this->postJson("/api/v1/affectations/{$affectation->id}/casualties", [
        'type' => 'injured',
    ])->assertForbidden();
});

it('counts only vivienda property types as housing in the balance', function () {
    $severities = derrumbesSeverities();

    $vivienda = makeAffectation(['incident_type_id' => $severities['incident_type_id']]);
    $vivienda->severities()->sync([$severities['total_severity_id']]);

    $edificio = makeAffectation(['incident_type_id' => $severities['incident_type_id']]);
    $edificio->severities()->attach($severities['total_severity_id']);
    $edificio->propertyTypes()->sync([
        PropertyType::query()->firstOrCreate(['code' => 'edificio'], ['display_name' => 'Edificio'])->id,
    ]);

    $summary = app(BalanceService::class)->summary();

    expect($summary['viviendas_afectadas'])->toBe(1)
        ->and($summary['viviendas_destruidas'])->toBe(1)
        ->and($summary['viviendas_averiadas'])->toBe(0);
});
