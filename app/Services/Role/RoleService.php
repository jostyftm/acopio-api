<?php

namespace App\Services\Role;

use App\Exceptions\ApiException;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    private const GUARD = 'api';

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        $role = Role::query()->create(['name' => $data['name'], 'guard_name' => self::GUARD]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->fresh('permissions');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->fresh('permissions');
    }

    public function destroy(Role $role): void
    {
        $inUse = DB::table('model_has_roles')->where('role_id', $role->id)->exists();

        if ($inUse) {
            throw new ApiException(__('messages.role_in_use'), 422);
        }

        $role->delete();
    }
}
