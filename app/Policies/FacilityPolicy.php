<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\User;

class FacilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('facilities.manage');
    }

    public function view(User $user, Facility $facility): bool
    {
        return $user->can('facilities.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('facilities.manage');
    }

    public function update(User $user, Facility $facility): bool
    {
        if (! $user->can('facilities.manage')) {
            return false;
        }

        return $user->hasRole('admin', 'api')
            || (int) $facility->organization_id === (int) $user->organization_id;
    }

    public function delete(User $user, Facility $facility): bool
    {
        if (! $user->can('facilities.manage')) {
            return false;
        }

        return $user->hasRole('admin', 'api')
            || (int) $facility->organization_id === (int) $user->organization_id;
    }
}
