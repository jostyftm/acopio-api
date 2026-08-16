<?php

namespace App\Policies;

use App\Models\FacilityType;
use App\Models\User;

class FacilityTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('facility-types.list');
    }

    public function view(User $user, FacilityType $facilityType): bool
    {
        return $user->can('facility-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('facility-types.create');
    }

    public function update(User $user, FacilityType $facilityType): bool
    {
        return $user->can('facility-types.update');
    }

    public function delete(User $user, FacilityType $facilityType): bool
    {
        return $user->can('facility-types.delete');
    }
}
