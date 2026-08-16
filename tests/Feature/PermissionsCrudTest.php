<?php

use App\Models\Module;
use App\Models\Permission;
use App\Policies\AffectationPolicy;
use App\Policies\FacilityPolicy;
use App\Policies\FacilityTypePolicy;
use App\Policies\ModulePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\OrganizationTypePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PersonPolicy;
use App\Policies\RolePolicy;
use App\Policies\SearchReportPolicy;
use App\Policies\UserPolicy;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('defines the full CRUD permission set for every resource module', function () {
    $actions = ['view', 'list', 'create', 'update', 'delete'];

    $resourceModules = Module::query()
        ->whereNotNull('path')
        ->where('key', '!=', 'dashboard')
        ->pluck('key')
        ->all();

    expect($resourceModules)->not->toBeEmpty();

    foreach ($resourceModules as $key) {
        $expected = array_map(fn (string $action): string => "{$key}.{$action}", $actions);

        expect(
            Permission::query()->whereIn('name', $expected)->pluck('name')->sort()->values()->all(),
        )->toBe(
            collect($expected)->sort()->values()->all(),
            "el módulo {$key} debe definir el CRUD completo",
        );
    }
});

it('maps every module CRUD action to its policy method', function () {
    $policies = [
        'people' => PersonPolicy::class,
        'search-reports' => SearchReportPolicy::class,
        'organizations' => OrganizationPolicy::class,
        'facilities' => FacilityPolicy::class,
        'users' => UserPolicy::class,
        'my-staff' => UserPolicy::class,
        'affectations' => AffectationPolicy::class,
        'roles' => RolePolicy::class,
        'permissions' => PermissionPolicy::class,
        'modules' => ModulePolicy::class,
        'organization-types' => OrganizationTypePolicy::class,
        'facility-types' => FacilityTypePolicy::class,
    ];

    $methods = [
        'view' => 'view',
        'list' => 'viewAny',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
    ];

    foreach ($policies as $key => $policy) {
        $instance = app($policy);

        foreach ($methods as $action => $method) {
            expect(
                method_exists($instance, $method),
                "el módulo {$key} debe exponer el método {$method} en su policy",
            )->toBeTrue();
        }
    }

    expect(method_exists(app(PersonPolicy::class), 'verify'))->toBeTrue();
});
