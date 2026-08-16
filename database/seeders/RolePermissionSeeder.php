<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private const GUARD = 'api';

    /**
     * @var array<string, list<string>>
     */
    private const PERMISSIONS = [
        'admin' => [
            'dashboard.view',
            'people.view',
            'people.list',
            'people.create',
            'people.update',
            'people.verify',
            'people.delete',
            'search-reports.view',
            'search-reports.list',
            'search-reports.create',
            'search-reports.update',
            'search-reports.delete',
            'stats.view',
            'users.view',
            'users.list',
            'users.create',
            'users.update',
            'users.delete',
            'needs.manage',
            'organizations.view',
            'organizations.list',
            'organizations.create',
            'organizations.update',
            'organizations.delete',
            'facilities.view',
            'facilities.list',
            'facilities.create',
            'facilities.update',
            'facilities.delete',
            'roles.view',
            'roles.list',
            'roles.create',
            'roles.update',
            'roles.delete',
            'permissions.view',
            'permissions.list',
            'permissions.create',
            'permissions.update',
            'permissions.delete',
            'modules.view',
            'modules.list',
            'modules.create',
            'modules.update',
            'modules.delete',
            'organization-types.view',
            'organization-types.list',
            'organization-types.create',
            'organization-types.update',
            'organization-types.delete',
            'facility-types.view',
            'facility-types.list',
            'facility-types.create',
            'facility-types.update',
            'facility-types.delete',
            'affectations.view',
            'affectations.list',
            'affectations.create',
            'affectations.update',
            'affectations.delete',
        ],
        'org_admin' => [
            'people.view',
            'people.list',
            'people.update',
            'people.verify',
            'search-reports.view',
            'search-reports.list',
            'search-reports.create',
            'search-reports.update',
            'search-reports.delete',
            'stats.view',
            'users.view',
            'users.list',
            'users.create',
            'users.update',
            'users.delete',
            'facilities.view',
            'facilities.list',
            'facilities.create',
            'facilities.update',
            'facilities.delete',
            'dashboard.view',
            'affectations.view',
            'affectations.list',
            'affectations.create',
            'affectations.update',
        ],
        'operator' => [
            'people.view',
            'people.list',
            'people.update',
            'people.verify',
            'search-reports.view',
            'search-reports.list',
            'search-reports.create',
            'search-reports.update',
            'search-reports.delete',
            'stats.view',
            'dashboard.view',
            'affectations.view',
            'affectations.list',
            'affectations.create',
            'affectations.update',
        ],
        'viewer' => [
            'people.view',
            'people.list',
            'search-reports.view',
            'search-reports.list',
            'dashboard.view',
            'affectations.view',
            'affectations.list',
        ],
    ];

    /**
     * Módulos del sistema con sus permisos asociados.
     *
     * @var array<string, array{
     *     name: string,
     *     path: string|null,
     *     icon: string|null,
     *     order: int,
     *     parent: string|null,
     *     permissions: list<string>
     * }>
     */
    private const MODULES = [
        'dashboard' => [
            'name' => 'Panel',
            'path' => '/dashboard',
            'icon' => 'LayoutDashboard',
            'order' => 1,
            'parent' => null,
            'permissions' => ['dashboard.view'],
        ],
        'people' => [
            'name' => 'Personas',
            'path' => '/people',
            'icon' => 'Users',
            'order' => 2,
            'parent' => null,
            'permissions' => ['people.view', 'people.list', 'people.create', 'people.update', 'people.verify', 'people.delete'],
        ],
        'search-reports' => [
            'name' => 'Búsquedas',
            'path' => '/search-reports',
            'icon' => 'Search',
            'order' => 3,
            'parent' => null,
            'permissions' => ['search-reports.view', 'search-reports.list', 'search-reports.create', 'search-reports.update', 'search-reports.delete'],
        ],
        'organizations' => [
            'name' => 'Organizaciones',
            'path' => '/organizations',
            'icon' => 'Building2',
            'order' => 4,
            'parent' => null,
            'permissions' => ['organizations.view', 'organizations.list', 'organizations.create', 'organizations.update', 'organizations.delete'],
        ],
        'facilities' => [
            'name' => 'Centros y albergues',
            'path' => '/facilities',
            'icon' => 'Warehouse',
            'order' => 5,
            'parent' => null,
            'permissions' => ['facilities.view', 'facilities.list', 'facilities.create', 'facilities.update', 'facilities.delete'],
        ],
        'users' => [
            'name' => 'Usuarios',
            'path' => '/users',
            'icon' => 'UserCog',
            'order' => 6,
            'parent' => null,
            'permissions' => ['users.view', 'users.list', 'users.create', 'users.update', 'users.delete'],
        ],
        'affectations' => [
            'name' => 'Afectaciones',
            'path' => '/afectaciones',
            'icon' => 'Ambulance',
            'order' => 7,
            'parent' => null,
            'permissions' => ['affectations.view', 'affectations.list', 'affectations.create', 'affectations.update', 'affectations.delete'],
        ],
        'system' => [
            'name' => 'Administración del sistema',
            'path' => null,
            'icon' => 'Settings',
            'order' => 8,
            'parent' => null,
            'permissions' => [],
        ],
        'roles' => [
            'name' => 'Roles',
            'path' => '/roles',
            'icon' => 'Shield',
            'order' => 1,
            'parent' => 'system',
            'permissions' => ['roles.view', 'roles.list', 'roles.create', 'roles.update', 'roles.delete'],
        ],
        'permissions' => [
            'name' => 'Permisos',
            'path' => '/permissions',
            'icon' => 'Key',
            'order' => 2,
            'parent' => 'system',
            'permissions' => ['permissions.view', 'permissions.list', 'permissions.create', 'permissions.update', 'permissions.delete'],
        ],
        'modules' => [
            'name' => 'Módulos',
            'path' => '/modules',
            'icon' => 'LayoutGrid',
            'order' => 3,
            'parent' => 'system',
            'permissions' => ['modules.view', 'modules.list', 'modules.create', 'modules.update', 'modules.delete'],
        ],
        'organization-types' => [
            'name' => 'Tipos de organización',
            'path' => '/organization-types',
            'icon' => 'Building',
            'order' => 4,
            'parent' => 'system',
            'permissions' => ['organization-types.view', 'organization-types.list', 'organization-types.create', 'organization-types.update', 'organization-types.delete'],
        ],
        'facility-types' => [
            'name' => 'Tipos de centro',
            'path' => '/facility-types',
            'icon' => 'Boxes',
            'order' => 5,
            'parent' => 'system',
            'permissions' => ['facility-types.view', 'facility-types.list', 'facility-types.create', 'facility-types.update', 'facility-types.delete'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = array_unique(array_merge(...array_values(self::PERMISSIONS)));

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        foreach (self::PERMISSIONS as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => self::GUARD]);
            $role->syncPermissions($rolePermissions);
        }

        $this->seedModules();
    }

    private function seedModules(): void
    {
        $modules = [];

        foreach (self::MODULES as $key => $data) {
            $modules[$key] = Module::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $data['name'],
                    'path' => $data['path'],
                    'icon' => $data['icon'],
                    'order' => $data['order'],
                    'display_sidebar' => true,
                    'is_active' => true,
                ],
            );
        }

        foreach (self::MODULES as $key => $data) {
            if ($data['parent'] !== null) {
                $modules[$key]->update(['parent_id' => $modules[$data['parent']]->id]);
            }
        }

        foreach (self::MODULES as $key => $data) {
            foreach ($data['permissions'] as $name) {
                Permission::query()
                    ->where('name', $name)
                    ->where('guard_name', self::GUARD)
                    ->update(['module_id' => $modules[$key]->id]);
            }
        }
    }
}
