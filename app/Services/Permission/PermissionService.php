<?php

namespace App\Services\Permission;

use App\Models\Module;
use App\Models\Permission;

class PermissionService
{
    private const GUARD = 'api';

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Permission
    {
        return Permission::query()->create([
            'name' => $this->composeName($data['module_id'], $data['action']),
            'guard_name' => self::GUARD,
            'module_id' => $data['module_id'],
            'display_name' => $data['display_name'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Permission $permission, array $data): Permission
    {
        $permission->update([
            'name' => $this->composeName($data['module_id'], $data['action']),
            'module_id' => $data['module_id'],
            'display_name' => $data['display_name'] ?? $permission->display_name,
        ]);

        return $permission->fresh('module');
    }

    public function destroy(Permission $permission): void
    {
        $permission->delete();
    }

    private function composeName(int $moduleId, string $action): string
    {
        $module = Module::query()->findOrFail($moduleId);

        return "{$module->key}.{$action}";
    }
}
