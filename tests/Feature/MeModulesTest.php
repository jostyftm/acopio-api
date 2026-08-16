<?php

use App\Models\Module;
use App\Models\Permission;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('requires authentication to list modules', function () {
    $this->getJson('/api/v1/me/modules')->assertStatus(401);
});

it('returns only accessible modules for a viewer', function () {
    $viewer = makeUserWithRole('viewer');
    Passport::actingAs($viewer);

    $response = $this->getJson('/api/v1/me/modules')->assertOk()->assertJsonCount(4, 'data');

    $keys = collect($response->json('data'))->pluck('key')->all();
    expect($keys)->toBe(['dashboard', 'people', 'search-reports', 'affectations']);
});

it('maps permissions as booleans per action', function () {
    $operator = makeUserWithRole('operator');
    Passport::actingAs($operator);

    $this->getJson('/api/v1/me/modules')
        ->assertOk()
        ->assertJsonPath('data.1.key', 'people')
        ->assertJsonPath('data.1.permissions.view', true)
        ->assertJsonPath('data.1.permissions.create', false)
        ->assertJsonPath('data.1.permissions.update', true)
        ->assertJsonPath('data.1.permissions.verify', true)
        ->assertJsonPath('data.1.permissions.delete', false);
});

it('returns the system group with its children only for admins', function () {
    $admin = makeUserWithRole('admin');
    Passport::actingAs($admin);

    $response = $this->getJson('/api/v1/me/modules')->assertOk()->assertJsonCount(8, 'data');

    $system = collect($response->json('data'))->firstWhere('key', 'system');

    expect(collect($system['children'])->pluck('key')->all())->toBe([
        'roles',
        'permissions',
        'modules',
        'organization-types',
        'facility-types',
        'property-types',
        'incident-types',
    ]);
});

it('hides the system group for organization admins without management access', function () {
    $orgAdmin = makeUserWithRole('org_admin');
    Passport::actingAs($orgAdmin);

    $response = $this->getJson('/api/v1/me/modules')
        ->assertOk()
        ->assertJsonCount(6, 'data')
        ->assertJsonPath('data.2.key', 'search-reports')
        ->assertJsonPath('data.3.key', 'facilities')
        ->assertJsonPath('data.4.key', 'my-staff')
        ->assertJsonPath('data.5.key', 'affectations')
        ->assertJsonPath('data.5.permissions.view', true)
        ->assertJsonPath('data.5.permissions.list', true)
        ->assertJsonPath('data.5.permissions.create', true)
        ->assertJsonPath('data.5.permissions.update', true)
        ->assertJsonPath('data.5.permissions.delete', true);

    $keys = collect($response->json('data'))->pluck('key')->all();
    expect($keys)->not->toContain('system');
});

it('hides inactive modules', function () {
    $viewer = makeUserWithRole('viewer');

    $module = Module::query()->create([
        'key' => 'reporting',
        'name' => 'Reportes',
        'path' => '/reporting',
        'is_active' => false,
    ]);

    $permission = Permission::query()->firstOrCreate(['name' => 'reporting.view', 'guard_name' => 'api'], ['module_id' => $module->id]);
    $permission->update(['module_id' => $module->id]);
    $viewer->givePermissionTo($permission);

    Passport::actingAs($viewer);

    $this->getJson('/api/v1/me/modules')
        ->assertOk()
        ->assertJsonCount(4, 'data');
});

it('hides active modules without granted permissions', function () {
    $viewer = makeUserWithRole('viewer');

    Module::query()->create([
        'key' => 'audit',
        'name' => 'Auditoría',
        'path' => '/audit',
        'is_active' => true,
    ]);

    Permission::query()->create(['name' => 'audit.view', 'guard_name' => 'api']);

    Passport::actingAs($viewer);

    $response = $this->getJson('/api/v1/me/modules')->assertOk()->assertJsonCount(4, 'data');

    $keys = collect($response->json('data'))->pluck('key')->all();
    expect($keys)->not->toContain('audit');
});
