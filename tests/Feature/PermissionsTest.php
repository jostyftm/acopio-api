<?php

use App\Models\Module;
use App\Models\Permission;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lists the permissions with their module', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $this->getJson('/api/v1/permissions')
        ->assertOk()
        ->assertJsonCount(18, 'data')
        ->assertJsonPath('data.0.attributes.module.key', 'dashboard');
});

it('creates a permission composing the name from the module and action', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $module = Module::query()->where('key', 'people')->firstOrFail();

    $this->postJson('/api/v1/permissions', [
        'module_id' => $module->id,
        'action' => 'export',
    ])->assertCreated()
        ->assertJsonPath('data.attributes.name', 'people.export')
        ->assertJsonPath('data.attributes.action', 'export')
        ->assertJsonPath('data.attributes.module_id', $module->id);
});

it('rejects a duplicated composed permission name', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $module = Module::query()->where('key', 'people')->firstOrFail();

    $this->postJson('/api/v1/permissions', [
        'module_id' => $module->id,
        'action' => 'view',
    ])->assertUnprocessable()->assertJsonValidationErrors('action');
});

it('updates a permission and recomposes its name', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $module = Module::query()->where('key', 'people')->firstOrFail();
    $permission = Permission::query()->create([
        'name' => 'people.export',
        'guard_name' => 'api',
        'module_id' => $module->id,
    ]);

    $this->putJson("/api/v1/permissions/{$permission->id}", [
        'module_id' => $module->id,
        'action' => 'import',
    ])->assertOk()
        ->assertJsonPath('data.attributes.name', 'people.import')
        ->assertJsonPath('data.attributes.action', 'import');
});

it('deletes a permission', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $module = Module::query()->where('key', 'people')->firstOrFail();
    $permission = Permission::query()->create([
        'name' => 'people.export',
        'guard_name' => 'api',
        'module_id' => $module->id,
    ]);

    $this->deleteJson("/api/v1/permissions/{$permission->id}")->assertNoContent();

    expect(Permission::find($permission->id))->toBeNull();
});

it('validates permission creation', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $this->postJson('/api/v1/permissions', ['action' => 'export'])->assertUnprocessable()->assertJsonValidationErrors('module_id');

    $module = Module::query()->where('key', 'people')->firstOrFail();

    $this->postJson('/api/v1/permissions', [
        'module_id' => $module->id,
        'action' => 'Espacio Invalido',
    ])->assertUnprocessable()->assertJsonValidationErrors('action');
});

it('rejects non admins from managing permissions', function () {
    Passport::actingAs(makeUserWithRole('org_admin'));

    $this->getJson('/api/v1/permissions')->assertStatus(403);

    $module = Module::query()->where('key', 'people')->firstOrFail();

    $this->postJson('/api/v1/permissions', [
        'module_id' => $module->id,
        'action' => 'export',
    ])->assertStatus(403);
});
