<?php

namespace App\Policies;

use App\Models\IncidentType;
use App\Models\User;

class IncidentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('incident-types.list');
    }

    public function view(User $user, IncidentType $incidentType): bool
    {
        return $user->can('incident-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('incident-types.create');
    }

    public function update(User $user, IncidentType $incidentType): bool
    {
        return $user->can('incident-types.update');
    }

    public function delete(User $user, IncidentType $incidentType): bool
    {
        return $user->can('incident-types.delete');
    }
}
