<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OrganizationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Organization
    {
        return Organization::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update($data);

        return $organization->fresh();
    }

    /**
     * Crea un usuario dentro de una organización con rol "operator".
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function createUser(Organization $organization, array $data): User
    {
        $role = Role::findByName('operator', 'api');

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'organization_id' => $organization->id,
        ]);

        $user->syncRoles([$role]);

        return $user->fresh();
    }
}
