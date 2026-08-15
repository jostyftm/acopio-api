<?php

namespace App\Policies;

use App\Models\Need;
use App\Models\User;

class NeedPolicy
{
    public function create(User $user): bool
    {
        return $user->can('needs.manage');
    }

    public function viewAny(User $user, ?Need $need = null): bool
    {
        return true;
    }
}
