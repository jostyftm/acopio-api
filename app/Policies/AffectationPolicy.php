<?php

namespace App\Policies;

use App\Models\Affectation;
use App\Models\User;

class AffectationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('affectations.list');
    }

    public function view(User $user, Affectation $affectation): bool
    {
        return $user->can('affectations.view')
            && $this->inScope($user, $affectation);
    }

    public function create(User $user): bool
    {
        return $user->can('affectations.create');
    }

    public function update(User $user, Affectation $affectation): bool
    {
        return $user->can('affectations.update')
            && $this->inScope($user, $affectation);
    }

    public function delete(User $user, Affectation $affectation): bool
    {
        return $user->can('affectations.delete')
            && $this->inScope($user, $affectation);
    }

    private function inScope(User $user, Affectation $affectation): bool
    {
        if ($user->hasRole('admin', 'api')) {
            return true;
        }

        if ($affectation->reported_by !== null && (int) $affectation->reported_by === (int) $user->id) {
            return true;
        }

        return $affectation->organization_id !== null
            && (int) $affectation->organization_id === (int) $user->organization_id;
    }
}
