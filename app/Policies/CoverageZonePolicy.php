<?php

namespace App\Policies;

use App\Models\CoverageZone;
use App\Models\User;

class CoverageZonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('coverage-zones.list');
    }

    public function view(User $user, CoverageZone $coverageZone): bool
    {
        return $user->can('coverage-zones.view');
    }

    public function create(User $user): bool
    {
        return $user->can('coverage-zones.create');
    }

    public function update(User $user, CoverageZone $coverageZone): bool
    {
        return $user->can('coverage-zones.update');
    }

    public function delete(User $user, CoverageZone $coverageZone): bool
    {
        return $user->can('coverage-zones.delete');
    }
}
