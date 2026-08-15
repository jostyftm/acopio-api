<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as PassportClientModel;

class PassportClient extends PassportClientModel
{
    /**
     * Skip the authorization approval screen for first-party clients
     * (clients without an owning user, i.e. the SPA and internal clients).
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return $this->firstParty();
    }
}
