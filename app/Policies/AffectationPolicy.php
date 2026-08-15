<?php

namespace App\Policies;

use App\Models\Affectation;
use App\Models\User;

class AffectationPolicy
{
    public function update(User $user, Affectation $affectation): bool
    {
        return $user->can('people.update');
    }
}
