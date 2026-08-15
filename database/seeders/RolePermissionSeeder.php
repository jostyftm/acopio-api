<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
        ],
        'operator' => [
            'people.view',
            'people.update',
            'people.verify',
            'search-reports.view',
            'search-reports.manage',
            'stats.view',
        ],
        'viewer' => [
            'people.view',
            'search-reports.view',
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
    }
}
