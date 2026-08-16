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
            'people.view',
            'people.create',
            'people.update',
            'people.verify',
            'people.delete',
            'search-reports.view',
            'search-reports.manage',
            'stats.view',
            'users.manage',
            'needs.manage',
            'organizations.manage',
            'facilities.manage',
            'dashboard.view',
            'roles.manage',
            'permissions.manage',
            'modules.manage',
            'organization-types.manage',
            'facility-types.manage',
            'afectaciones.view',
            'afectaciones.create',
            'afectaciones.update',
        ],
        'org_admin' => [
            'people.view',
            'people.update',
            'people.verify',
            'search-reports.view',
            'search-reports.manage',
            'stats.view',
            'users.manage',
            'facilities.manage',
            'dashboard.view',
            'afectaciones.view',
            'afectaciones.create',
            'afectaciones.update',
        ],
        'operator' => [
            'people.view',
            'people.update',
            'people.verify',
            'search-reports.view',
            'search-reports.manage',
            'stats.view',
            'dashboard.view',
            'afectaciones.view',
            'afectaciones.create',
            'afectaciones.update',
        ],
        'viewer' => [
            'people.view',
            'search-reports.view',
            'dashboard.view',
            'afectaciones.view',
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
            'permissions' => ['people.view', 'people.create', 'people.update', 'people.verify', 'people.delete'],
        ],
        'search-reports' => [
            'name' => 'Búsquedas',
            'path' => '/search-reports',
            'icon' => 'Search',
            'order' => 3,
            'parent' => null,
            'permissions' => ['search-reports.view', 'search-reports.manage'],
        ],
        'organizations' => [
            'name' => 'Organizaciones',
            'path' => '/organizations',
            'icon' => 'Building2',
            'order' => 4,
            'parent' => null,
            'permissions' => ['organizations.manage'],
        ],
        'facilities' => [
            'name' => 'Centros y albergues',
            'path' => '/facilities',
            'icon' => 'Warehouse',
            'order' => 5,
            'parent' => null,
            'permissions' => ['facilities.manage'],
        ],
        'users' => [
            'name' => 'Usuarios',
            'path' => '/users',
            'icon' => 'UserCog',
            'order' => 6,
            'parent' => null,
            'permissions' => ['users.manage'],
        ],
        'afectaciones' => [
            'name' => 'Afectaciones',
            'path' => '/afectaciones',
            'icon' => 'Ambulance',
            'order' => 7,
            'parent' => null,
            'permissions' => ['afectaciones.view', 'afectaciones.create', 'afectaciones.update'],
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
            'permissions' => ['roles.manage'],
        ],
        'permissions' => [
            'name' => 'Permisos',
            'path' => '/permissions',
            'icon' => 'Key',
            'order' => 2,
            'parent' => 'system',
            'permissions' => ['permissions.manage'],
        ],
        'modules' => [
            'name' => 'Módulos',
            'path' => '/modules',
            'icon' => 'LayoutGrid',
            'order' => 3,
            'parent' => 'system',
            'permissions' => ['modules.manage'],
        ],
        'organization-types' => [
            'name' => 'Tipos de organización',
            'path' => '/organization-types',
            'icon' => 'Building',
            'order' => 4,
            'parent' => 'system',
            'permissions' => ['organization-types.manage'],
        ],
        'facility-types' => [
            'name' => 'Tipos de centro',
            'path' => '/facility-types',
            'icon' => 'Boxes',
            'order' => 5,
            'parent' => 'system',
            'permissions' => ['facility-types.manage'],
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
