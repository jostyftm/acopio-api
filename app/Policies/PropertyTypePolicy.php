<?php

namespace App\Policies;

use App\Models\PropertyType;
use App\Models\User;

class PropertyTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('property-types.list');
    }

    public function view(User $user, PropertyType $propertyType): bool
    {
        return $user->can('property-types.view');
    }

    public function create(User $user): bool
    {
        return $user->can('property-types.create');
    }

    public function update(User $user, PropertyType $propertyType): bool
    {
        return $user->can('property-types.update');
    }

    public function delete(User $user, PropertyType $propertyType): bool
    {
        return $user->can('property-types.delete');
    }
}
