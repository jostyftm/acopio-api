<?php

use App\Models\Module;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Passport\Passport;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lists the module tree for an admin', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $this->getJson('/api/v1/modules')
        ->assertOk()
        ->assertJsonCount(7, 'data')
        ->assertJsonPath('data.0.attributes.key', 'dashboard')
        ->assertJsonPath('data.6.attributes.key', 'system')
        ->assertJsonCount(5, 'data.6.attributes.children');
});

it('creates a module', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $this->postJson('/api/v1/modules', [
        'parent_id' => Module::query()->where('key', 'system')->value('id'),
        'key' => 'reports',
        'name' => 'Reportes',
        'path' => '/reports',
        'icon' => 'FileText',
        'order' => 8,
    ])->assertCreated()
        ->assertJsonPath('data.attributes.key', 'reports')
        ->assertJsonPath('data.attributes.parent_id', Module::query()->where('key', 'system')->value('id'));
});

it('updates a module', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $module = Module::factory()->create(['key' => 'temp', 'name' => 'Temporal']);

    $this->putJson("/api/v1/modules/{$module->id}", [
        'key' => 'renamed',
        'name' => 'Renombrado',
        'display_sidebar' => false,
        'is_active' => false,
    ])->assertOk()
        ->assertJsonPath('data.attributes.name', 'Renombrado')
        ->assertJsonPath('data.attributes.display_sidebar', false)
        ->assertJsonPath('data.attributes.is_active', false);
});

it('deletes a module', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $module = Module::factory()->create(['key' => 'temp']);

    $this->deleteJson("/api/v1/modules/{$module->id}")->assertNoContent();

    expect(Module::find($module->id))->toBeNull();
});

it('validates module creation', function () {
    Passport::actingAs(makeUserWithRole('admin'));

    $this->postJson('/api/v1/modules', ['name' => 'Sin llave'])->assertUnprocessable()->assertJsonValidationErrors('key');

    $this->postJson('/api/v1/modules', [
        'key' => 'people',
        'name' => 'Duplicado',
    ])->assertUnprocessable()->assertJsonValidationErrors('key');

    $this->postJson('/api/v1/modules', [
        'key' => 'ConMayuscula',
        'name' => 'Formato inválido',
    ])->assertUnprocessable()->assertJsonValidationErrors('key');
});

it('rejects non admins from managing modules', function () {
    Passport::actingAs(makeUserWithRole('org_admin'));

    $this->getJson('/api/v1/modules')->assertStatus(403);
    $this->postJson('/api/v1/modules', [
        'key' => 'reports',
        'name' => 'Reportes',
    ])->assertStatus(403);
});
