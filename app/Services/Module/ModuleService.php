<?php

namespace App\Services\Module;

use App\Models\Module;
use App\Models\User;

class ModuleService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Module
    {
        return Module::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Module $module, array $data): Module
    {
        $module->update($data);

        return $module->fresh();
    }

    public function destroy(Module $module): void
    {
        $module->delete();
    }

    /**
     * Builds the tree of modules the user can access, with the permission
     * map per module used by the frontend to show or hide actions.
     *
     * @return list<array<string, mixed>>
     */
    public function treeFor(User $user): array
    {
        $modules = Module::query()
            ->with([
                'permissions',
                'children' => fn ($query) => $query->orderBy('order'),
                'children.permissions',
                'children.children' => fn ($query) => $query->orderBy('order'),
            ])
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return $modules
            ->map(fn (Module $module) => $this->buildNode($module, $user))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildNode(Module $module, User $user): ?array
    {
        $children = $module->children
            ->map(fn (Module $child) => $this->buildNode($child, $user))
            ->filter()
            ->values()
            ->all();

        $permissions = $this->permissionMap($module, $user);

        $hasPermissions = $module->permissions->isNotEmpty();
        $hasAnyPermission = in_array(true, $permissions, true);
        $hasChildren = $children !== [];

        if (! $hasChildren && (! $hasPermissions || ! $hasAnyPermission)) {
            return null;
        }

        return [
            'id' => $module->id,
            'parent_id' => $module->parent_id,
            'key' => $module->key,
            'name' => $module->name,
            'path' => $module->path,
            'icon' => $module->icon,
            'order' => $module->order,
            'permissions' => $permissions,
            'children' => $children,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function permissionMap(Module $module, User $user): array
    {
        $map = [];

        foreach ($module->permissions as $permission) {
            $map[$this->action($permission->name)] = $user->can($permission->name);
        }

        return $map;
    }

    private function action(string $name): string
    {
        $separator = strpos($name, '.');

        return $separator === false ? $name : substr($name, $separator + 1);
    }
}
