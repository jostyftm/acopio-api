<?php

use App\Models\Person;
use App\Models\SearchReport;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

it('returns aggregated statistics for authenticated users', function () {
    $this->seed(RolePermissionSeeder::class);
    Person::factory()->count(3)->create(['municipality' => 'Cali', 'latitude' => null, 'longitude' => null]);
    Person::factory()->located()->create([
        'municipality' => 'Cali',
        'latitude' => 3.42,
        'longitude' => -76.52,
    ]);
    Person::factory()->verified()->create([
        'municipality' => 'Buenaventura',
        'latitude' => null,
        'longitude' => null,
    ]);
    SearchReport::factory()->create(['status' => 'pending', 'person_id' => null]);

    $user = User::factory()->create();
    $user->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($user);

    $this->getJson('/api/v1/stats')
        ->assertOk()
        ->assertJsonPath('data.total_people', 5)
        ->assertJsonPath('data.by_status.registered', 3)
        ->assertJsonPath('data.by_status.verified', 1)
        ->assertJsonPath('data.by_status.located', 1)
        ->assertJsonPath('data.pending_search_reports', 1)
        ->assertJsonPath('data.people_with_location', 1);
});
