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
        ]);

        if (isset($data['role'])) {
            $user->syncRoles([Role::findByName($data['role'], 'api')]);
        }

        return $user->fresh();
    }
}
