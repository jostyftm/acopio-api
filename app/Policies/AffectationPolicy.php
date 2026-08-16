<?php

namespace App\Policies;

use App\Models\Affectation;
use App\Models\User;

class AffectationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('afectaciones.view');
    }

    public function view(User $user, Affectation $affectation): bool
    {
        return $user->can('afectaciones.view');
    }

    public function update(User $user, Affectation $affectation): bool
    {
        return $user->can('afectaciones.update');
    }
}
