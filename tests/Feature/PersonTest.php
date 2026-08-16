<?php

use App\Models\Person;
use App\Models\SearchReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function makePeopleAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));

    return $admin;
}

it('shows a person detail with affectation, verifier and search reports', function () {
    Passport::actingAs(makePeopleAdmin());

    $affectation = makeAffectation(['description' => 'Vivienda afectada por deslizamiento']);
    $person = $affectation->person;
    $report = SearchReport::factory()->create(['person_id' => $person->id]);

    $this->getJson("/api/v1/people/{$person->id}")
        ->assertOk()
        ->assertJsonPath('data.attributes.full_name', $person->full_name)
        ->assertJsonPath('data.relationships.affectation.incident_type.display_name', 'Derrumbes')
        ->assertJsonPath('data.relationships.affectation.severities.0.display_name', 'Afectación parcial')
        ->assertJsonCount(1, 'data.relationships.search_reports')
        ->assertJsonPath('data.relationships.search_reports.0.attributes.searched_name', $report->searched_name);
});

it('marks a person as verified without a location', function () {
    $admin = makePeopleAdmin();
    Passport::actingAs($admin);

    $person = Person::factory()->create();

    $this->postJson("/api/v1/people/{$person->id}/verify")
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'verified')
        ->assertJsonPath('data.relationships.verified_by.id', $admin->id);

    expect($person->refresh()->status->value)->toBe('verified');
});

it('marks a person as located when coordinates are provided', function () {
    Passport::actingAs(makePeopleAdmin());

    $person = Person::factory()->create();

    $this->postJson("/api/v1/people/{$person->id}/verify", [
        'latitude' => 3.8775248,
        'longitude' => -77.0206341,
    ])
        ->assertOk()
        ->assertJsonPath('data.attributes.status', 'located')
        ->assertJsonPath('data.attributes.latitude', 3.8775248)
        ->assertJsonPath('data.attributes.longitude', -77.0206341);

    expect($person->refresh()->status->value)->toBe('located');
});

it('rejects verification for a viewer without the people.verify permission', function () {
    $viewer = User::factory()->create();
    $viewer->assignRole(Role::findByName('viewer', 'api'));
    Passport::actingAs($viewer);

    $person = Person::factory()->create();

    $this->postJson("/api/v1/people/{$person->id}/verify")
        ->assertStatus(403);
});
