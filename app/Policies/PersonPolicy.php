<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('people.list');
    }

    public function view(User $user, Person $person): bool
    {
        return $user->can('people.view');
    }

    public function create(User $user): bool
    {
        return $user->can('people.create');
    }

    public function update(User $user, Person $person): bool
    {
        return $user->can('people.update');
    }

    public function verify(User $user, Person $person): bool
    {
        return $user->can('people.verify');
    }

    public function delete(User $user, Person $person): bool
    {
        return $user->can('people.delete');
    }
}
