<?php

namespace App\Http\Controllers\Api\V1\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Registration\CheckRegistrationRequest;
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

    /**
     * Registra a una persona damnificada.
     *
     * Crea un registro único de damnificado. Si la persona ya existe por
     * tipo y número de documento, devuelve el registro existente con el
     * flag `duplicate` en el `meta`.
     *
     * @param  StoreRegistrationRequest  $request  Datos del damnificado.
     */
    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        $person = $this->registrationService->register($request->validated());
        $person->load(['municipality', 'affectation.needs', 'affectation.evidence']);

        return ApiResponse::success(
            PersonResource::make($person),
            ['duplicate' => ! $person->wasRecentlyCreated],
            $person->wasRecentlyCreated ? 201 : 200,
        );
    }

    /**
     * Consulta si una persona ya está registrada y si tiene afectaciones.
     *
     * Alimenta el debounce del formulario de registro para advertir antes de
     * diligenciar todo el formulario.
     *
     * @param  CheckRegistrationRequest  $request  Tipo y número de documento.
     */
    public function check(CheckRegistrationRequest $request): JsonResponse
    {
        $person = $this->registrationService->findDuplicate(
            $request->string('document_type')->toString(),
            $request->string('document_number')->toString(),
        );

        if ($person === null) {
            return ApiResponse::success(['exists' => false]);
        }

        $person->load('affectation.needs');

        return ApiResponse::success([
            'exists' => true,
            'has_affectation' => $person->affectation !== null,
            'person' => [
                'full_name' => $person->full_name,
            ],
            'affectation' => $person->affectation === null ? null : [
                'severity' => $person->affectation->severity?->value,
                'needs' => $person->affectation->needs->pluck('name')->values(),
            ],
        ]);
    }
}
