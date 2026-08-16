<?php

namespace App\Policies;

use App\Models\OrganizationType;
use App\Models\User;

class OrganizationTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization-types.list');
    }

    public function view(User $user, OrganizationType $organizationType): bool
    {
        return $user->can('organization-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization-types.create');
    }

    public function update(User $user, OrganizationType $organizationType): bool
    {
        return $user->can('organization-types.update');
    }

    public function delete(User $user, OrganizationType $organizationType): bool
    {
        return $user->can('organization-types.delete');
    }
}
