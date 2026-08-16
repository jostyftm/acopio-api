<?php

namespace App\Http\Requests\Api\V1\Affectation;

use App\Http\Requests\Api\V1\Registration\StoreRegistrationRequest;
use App\Models\Affectation;

class StoreAffectationRequest extends StoreRegistrationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Affectation::class) ?? false;
    }
}
