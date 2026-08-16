<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('users.manage')
            && ($user->hasRole('admin', 'api') || $this->sameOrganization($user, $model));
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage')
            && ($user->hasRole('admin', 'api') || $this->sameOrganization($user, $model));
    }

    public function delete(User $user, User $model): bool
    {
        if (! $user->can('users.manage')) {
            return false;
        }

        if ($user->hasRole('admin', 'api')) {
            return ! $model->hasRole('admin', 'api');
        }

        return $this->sameOrganization($user, $model)
            && ! $model->hasAnyRole(['admin', 'org_admin'], 'api');
    }

    private function sameOrganization(User $user, User $model): bool
    {
        return $model->organization_id !== null
            && (int) $model->organization_id === (int) $user->organization_id;
    }
}
