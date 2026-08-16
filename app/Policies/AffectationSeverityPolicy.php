<?php

namespace App\Policies;

use App\Models\AffectationSeverity;
use App\Models\User;

class AffectationSeverityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('incident-types.view');
    }

    public function view(User $user, AffectationSeverity $affectationSeverity): bool
    {
        return $user->can('incident-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('incident-types.update');
    }

    public function update(User $user, AffectationSeverity $affectationSeverity): bool
    {
        return $user->can('incident-types.update');
    }

    public function delete(User $user, AffectationSeverity $affectationSeverity): bool
    {
        return $user->can('incident-types.update');
    }
}
