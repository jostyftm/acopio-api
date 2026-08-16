<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\User;

class FacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('facilities.list');
    }

    public function view(User $user, Facility $facility): bool
    {
        return $user->can('facilities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('facilities.create');
    }

    public function update(User $user, Facility $facility): bool
    {
        if (! $user->can('facilities.update')) {
            return false;
        }

        return $user->hasRole('admin', 'api')
            || (int) $facility->organization_id === (int) $user->organization_id;
    }

    public function delete(User $user, Facility $facility): bool
    {
        if (! $user->can('facilities.delete')) {
            return false;
        }

        return $user->hasRole('admin', 'api')
            || (int) $facility->organization_id === (int) $user->organization_id;
    }
}
