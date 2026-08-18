<?php

namespace App\Http\Controllers\Api\V1\Affectation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Affectation\StoreAffectationReportRequest;
use App\Http\Requests\Api\V1\Affectation\StoreAffectationRequest;
use App\Http\Requests\Api\V1\Affectation\UpdateAffectationRequest;
use App\Http\Requests\Api\V1\Affectation\VerifyAffectationRequest;
use App\Http\Resources\Api\V1\Affectation\AffectationResource;
use App\Http\Resources\Api\V1\Person\PersonResource;
use App\Models\Affectation;
use App\Models\Attachment;
use App\Services\Affectation\AffectationReportService;
use App\Services\Affectation\AffectationService;
use App\Services\Registration\RegistrationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AffectationController extends Controller
{
    public function __construct(
        private readonly AffectationService $affectationService,
        private readonly RegistrationService $registrationService,
        private readonly AffectationReportService $affectationReportService,
    ) {}

    /**
     * Registra una persona afectada desde el panel y marca su ubicación.
     *
     * Crea la persona y su afectación como localizada (`located`). Si la
     * persona ya existe por tipo y número de documento, devuelve el registro
     * existente con el flag `duplicate` en el `meta`. Requiere permiso de
     * creación del módulo de afectaciones.
     */
    public function store(StoreAffectationRequest $request): JsonResponse
    {
        $this->authorize('create', Affectation::class);

        $markLocated = $request->user()->can('people.verify');

        $person = $this->registrationService->register(
            $request->validated(),
            markLocated: $markLocated,
            reporter: $request->user(),
        );
        $person->load(['municipality', 'affectation.needs.severityNeed', 'affectation.incidentType', 'affectation.severities', 'affectation.propertyTypes', 'affectation.attachments', 'affectation.reporter', 'affectation.organization', 'affectation.status', 'affectation.verifiedBy', 'affectation.familyMembers.person.municipality', 'affectation.familyMembers.person.attachments']);

        return ApiResponse::success(
            PersonResource::make($person),
            ['duplicate' => ! $person->wasRecentlyCreated],
            $person->wasRecentlyCreated ? 201 : 200,
        );
    }

    /**
     * Registra un reporte de incidente sin persona asociada (escenario 1).
     *
     * Crea la afectación en estado `reported`: registrada pero sin verificar
     * ni localizar. No captura datos de persona; la verificación la realiza
     * posteriormente la organización que cubre la zona. Requiere permiso de
     * reporte del módulo de afectaciones.
     */
    public function storeReport(StoreAffectationReportRequest $request): JsonResponse
    {
        $this->authorize('report', Affectation::class);

        $affectation = $this->affectationReportService->report(
            $request->validated(),
            $request->user(),
        );

        $affectation->load(['person.municipality', 'needs.severityNeed', 'incidentType', 'severities', 'propertyTypes', 'attachments', 'reporter', 'organization', 'status', 'verifiedBy', 'familyMembers.person.municipality', 'familyMembers.person.attachments']);

        return ApiResponse::success(
            AffectationResource::make($affectation),
            [],
            201,
        );
    }

    /**
     * Verifica y/o localiza una afectación.
     *
     * Marca la afectación como verificada y, si se envían coordenadas, como
     * localizada. Si la afectación tiene persona asociada, delega en el
     * servicio de personas para mantener el estado consistente.
     *
     * @param  VerifyAffectationRequest  $request  Datos de verificación y ubicación opcional.
     * @param  Affectation  $affectation  La afectación a verificar.
     */
    public function verify(VerifyAffectationRequest $request, Affectation $affectation): JsonResponse
    {
        $this->authorize('verify', $affectation);

        $affectation = $this->affectationService->verify(
            $affectation,
            $request->user(),
            $request->validated(),
        );

        return ApiResponse::success(AffectationResource::make($affectation));
    }

    /**
     * Lista las afectaciones registradas.
     *
     * Requiere permiso de visualización del módulo de afectaciones.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Affectation::class);

        $affectations = AffectationResource::collection(
            $this->affectationService->indexQuery($request->user())->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($affectations);
    }

    /**
     * Muestra el detalle de una afectación con la información de la persona.
     *
     * Requiere permiso de visualización del módulo de afectaciones.
     */
    public function show(Affectation $affectation): JsonResponse
    {
        $this->authorize('view', $affectation);

        $affectation = $this->affectationService->loadRelations($affectation);

        return ApiResponse::success(AffectationResource::make($affectation));
    }

    /**
     * Actualiza la afectación de una persona.
     *
     * Permite corregir la severidad, descripción, ubicación del incidente
     * (cuando el censo se hizo en un lugar distinto), sincronizar las
     * necesidades y agregar nuevas evidencias. Requiere permiso de
     * actualización del módulo de afectaciones.
     */
    public function update(UpdateAffectationRequest $request, Affectation $affectation): JsonResponse
    {
        $this->authorize('update', $affectation);

        $affectation = $this->affectationService->update(
            $affectation,
            $request->validated(),
            $request->file('evidence', []),
        );

        return ApiResponse::success(AffectationResource::make($affectation));
    }

    /**
     * Elimina una afectación.
     *
     * Borra la afectación y sus evidencias del almacenamiento. La persona
     * permanece en el sistema. Requiere permiso de eliminación del módulo
     * de afectaciones.
     */
    public function destroy(Affectation $affectation): Response
    {
        $this->authorize('delete', $affectation);

        $this->affectationService->destroy($affectation);

        return response()->noContent();
    }

    /**
     * Elimina una evidencia subida a la afectación.
     *
     * Quita el archivo del almacenamiento y registra el borrado. Requiere
     * permiso de actualización del módulo de afectaciones.
     */
    public function destroyEvidence(Affectation $affectation, Attachment $evidence): JsonResponse
    {
        $this->authorize('update', $affectation);

        $this->affectationService->destroyEvidence($affectation, $evidence);

        return ApiResponse::success(['deleted' => true]);
    }
}
