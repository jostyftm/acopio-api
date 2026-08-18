<?php

namespace App\Services\User;

use App\Models\User;
use App\Support\Enums\SpatieRole;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserService
{
    /**
     * Build the query for listing users with filters and pagination.
     *
     * Admin users see all users; non-admin users see only those
     * belonging to their organization. Results include the organization
     * relationship and are sorted by creation date (newest first).
     *
     * @param  array{email?: string}  $filters
     */
    public function index(User $currentUser, array $filters, int $perPage = 15): CursorPaginator
    {
        $query = QueryBuilder::for(User::class)
            ->with('organization')
            ->allowedFilters(AllowedFilter::exact('email'))
            ->defaultSort('-created_at');

        if (! $currentUser->hasRole(SpatieRole::Admin->value, 'api')) {
            $query->where('organization_id', $currentUser->organization_id);
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * Create a new user with a role and sync it to the 'api' guard.
     *
     * @param  array{name: string, email: string, password: string, role: string, organization_id?: int}  $data
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
     * Update an existing user's data, optionally changing their role.
     *
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
     * Update the current user's own profile (name and email only).
     *
     * Does not allow changing roles, organization, or active status.
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
