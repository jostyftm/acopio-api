<?php

namespace App\Http\Controllers\Api\V1\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Registration\StoreRegistrationRequest;
use App\Http\Resources\Api\V1\Person\PersonResource;
use App\Services\Registration\RegistrationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        $person = $this->registrationService->register($request->validated());

        return ApiResponse::success(
            PersonResource::make($person),
            ['duplicate' => ! $person->wasRecentlyCreated],
            $person->wasRecentlyCreated ? 201 : 200,
        );
    }
}
