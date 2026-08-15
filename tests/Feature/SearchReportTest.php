<?php

use App\Models\Person;
use App\Models\SearchReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

it('creates a search report publicly with pending status', function () {
    $person = Person::factory()->create();

    $this->postJson('/api/v1/search-reports', [
        'person_id' => $person->id,
        'searched_name' => 'Juan Perez',
        'document_type' => 'CC',
        'document_number' => '123456789',
        'municipality' => 'Cali',
        'reporter_name' => 'Luisa Martinez',
        'reporter_phone' => '3009876543',
        'relationship' => 'Mother',
    ])->assertCreated()->assertJsonPath('data.attributes.status', 'pending');

    $this->assertDatabaseCount('search_reports', 1);
});

it('lists search reports for authenticated users', function () {
    $this->seed(RolePermissionSeeder::class);
    SearchReport::factory()->count(2)->create();
    $viewer = User::factory()->create();
    $viewer->assignRole(Role::findByName('viewer', 'api'));
    Passport::actingAs($viewer);

    $this->getJson('/api/v1/search-reports')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('rejects unauthenticated access to the search reports index', function () {
    SearchReport::factory()->create();

    $this->getJson('/api/v1/search-reports')->assertStatus(401);
});
