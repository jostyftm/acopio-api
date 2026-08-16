<?php

use App\Models\Affectation;
use App\Models\AffectationSeverity;
use App\Models\IncidentType;
use App\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Crea (idempotente) el tipo de incidente "derrumbes" con sus gravedades
 * y devuelve sus identificadores para usarlos en los tests.
 *
 * @return array{incident_type_id: int, partial_severity_id: int, total_severity_id: int}
 */
function derrumbesSeverities(): array
{
    $type = IncidentType::query()->firstOrCreate(
        ['code' => 'derrumbes'],
        ['display_name' => 'Derrumbes', 'severity_mode' => 'single'],
    );

    $partial = AffectationSeverity::query()->firstOrCreate(
        ['incident_type_id' => $type->id, 'code' => 'partial'],
        ['display_name' => 'Afectación parcial', 'order' => 1],
    );

    $total = AffectationSeverity::query()->firstOrCreate(
        ['incident_type_id' => $type->id, 'code' => 'total'],
        ['display_name' => 'Afectación total', 'order' => 2],
    );

    return [
        'incident_type_id' => $type->id,
        'partial_severity_id' => $partial->id,
        'total_severity_id' => $total->id,
    ];
}

/**
 * Crea una afectación con una persona nueva, tipo de incidente "derrumbes"
 * y gravedad parcial, devolviéndola lista para aserciones.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeAffectation(array $attributes = []): Affectation
{
    $severities = derrumbesSeverities();

    $affectation = Person::factory()->create()->affectation()->create([
        'incident_type_id' => $severities['incident_type_id'],
        ...$attributes,
    ]);
    $affectation->severities()->attach($severities['partial_severity_id']);

    return $affectation;
}
