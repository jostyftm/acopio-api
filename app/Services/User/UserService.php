<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        $role = Role::findByName($data['role'], 'api');

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'organization_id' => $data['organization_id'] ?? null,
        ]);

        $user->syncRoles([$role]);

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'password' => isset($data['password'])
                ? Hash::make($data['password'])
                : $user->password,
            'organization_id' => array_key_exists('organization_id', $data)
                ? $data['organization_id']
                : $user->organization_id,
            'is_active' => array_key_exists('is_active', $data)
                ? $data['is_active']
                : $user->is_active,
        ]);

        if (isset($data['role'])) {
            $user->syncRoles([Role::findByName($data['role'], 'api')]);
        }

        return $user->fresh();
    }

    /**
     * Actualiza el perfil del propio usuario.
     *
     * Solo permite modificar nombre y correo, sin tocar roles,
     * organización ni estado.
     *
     * @param  array{name: string, email: string}  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return $user->fresh();
    }
}
