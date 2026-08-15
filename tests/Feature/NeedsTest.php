<?php

use App\Models\Need;
use App\Models\SeverityNeed;
use App\Models\User;
use Database\Seeders\NeedsSeeder;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

it('lists active needs publicly', function () {
    $this->seed(NeedsSeeder::class);

    $data = $this->getJson('/api/v1/needs')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonPath('data.0.name', 'Adultos mayores')
        ->assertJsonPath('data.0.severity.code_level', 'low')
        ->assertJsonPath('data.0.severity.display_name', 'Bajo')
        ->json('data');

    $albergue = collect($data)->firstWhere('name', 'Albergue');
    expect($albergue['severity'])->toMatchArray(['code_level' => 'high', 'display_name' => 'Alto']);
});

it('seeds the three impact levels', function () {
    $this->seed(NeedsSeeder::class);

    $this->getJson('/api/v1/needs')
        ->assertOk()
        ->assertJsonCount(15, 'data')
        ->assertJsonFragment(['display_name' => 'Alto'])
        ->assertJsonFragment(['display_name' => 'Medio'])
        ->assertJsonFragment(['display_name' => 'Bajo']);
});

it('only lists active needs', function () {
    $level = SeverityNeed::query()->firstOrCreate(['code_level' => 'high'], ['display_name' => 'Alto']);
    Need::query()->create([
        'name' => 'Carpa',
        'normalized_name' => 'CARPA',
        'severity_need_id' => $level->id,
    ]);
    Need::query()->create([
        'name' => 'Kit de aseo',
        'normalized_name' => 'KIT DE ASEO',
        'active' => false,
        'severity_need_id' => $level->id,
    ]);

    $this->getJson('/api/v1/needs')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Carpa');
});

it('allows an admin to create a need', function () {
    $this->seed(RolePermissionSeeder::class);

    $level = SeverityNeed::query()->firstOrCreate(['code_level' => 'medium'], ['display_name' => 'Medio']);

    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($admin);

    $this->postJson('/api/v1/needs', [
        'name' => 'Carpa',
        'description' => 'Carpa para refugio temporal',
        'severity_need_id' => $level->id,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Carpa')
        ->assertJsonPath('data.description', 'Carpa para refugio temporal')
        ->assertJsonPath('data.severity.code_level', 'medium')
        ->assertJsonPath('data.severity.display_name', 'Medio');

    expect(Need::where('normalized_name', 'CARPA')->exists())->toBeTrue();
});

it('rejects an invalid or missing impact level when creating a need', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($admin);

    $this->postJson('/api/v1/needs', [
        'name' => 'Carpa',
        'severity_need_id' => 999999,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('severity_need_id');

    $this->postJson('/api/v1/needs', ['name' => 'Carpa'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('severity_need_id');
});

it('rejects duplicate needs ignoring accents and case', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(Role::findByName('admin', 'api'));
    Passport::actingAs($admin);

    $level = SeverityNeed::query()->firstOrCreate(['code_level' => 'medium'], ['display_name' => 'Medio']);

    Need::query()->create([
        'name' => 'Pañales',
        'normalized_name' => 'PANALES',
        'severity_need_id' => $level->id,
    ]);

    $this->postJson('/api/v1/needs', [
        'name' => 'Pañales',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('rejects creating a need without permission', function () {
    $this->seed(RolePermissionSeeder::class);

    $operator = User::factory()->create();
    $operator->assignRole(Role::findByName('operator', 'api'));
    Passport::actingAs($operator);

    $this->postJson('/api/v1/needs', ['name' => 'Carpa'])->assertForbidden();
});

it('rejects unauthenticated need creation', function () {
    $this->postJson('/api/v1/needs', ['name' => 'Carpa'])->assertStatus(401);
});
