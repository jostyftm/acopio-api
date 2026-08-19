<?php

namespace App\Http\Controllers\Api\V1\Affectation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Affectation\StoreAffectationReportRequest;
use App\Http\Requests\Api\V1\Affectation\StoreAffectationRequest;
use App\Http\Requests\Api\V1\Affectation\StoreCasualtyRequest;
use App\Http\Requests\Api\V1\Affectation\StoreFamilyMemberRequest;
use App\Http\Requests\Api\V1\Affectation\UpdateAffectationRequest;
use App\Http\Requests\Api\V1\Affectation\UpdateFamilyMemberRequest;
use App\Http\Requests\Api\V1\Affectation\VerifyAffectationRequest;
use App\Http\Resources\Api\V1\Affectation\AffectationResource;
use App\Http\Resources\Api\V1\FamilyMember\FamilyMemberResource;
use App\Http\Resources\Api\V1\Person\PersonResource;
use App\Models\Affectation;
use App\Models\Attachment;
use App\Models\FamilyMember;
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

    /**
     * Agrega un miembro familiar a una afectación.
     *
     * Crea o reutiliza la persona asociada y la vincula como miembro
     * del grupo familiar. Requiere permiso de actualización.
     */
    public function storeFamilyMember(StoreFamilyMemberRequest $request, Affectation $affectation): JsonResponse
    {
        $this->authorize('update', $affectation);

        $data = $request->validated();
        $familyMember = $this->affectationService->addFamilyMember($affectation, $data, $request->file('evidence', []));

        return ApiResponse::success(FamilyMemberResource::make($familyMember->load('person')), [], 201);
    }

    /**
     * Actualiza un miembro familiar de una afectación.
     *
     * Permite modificar los datos de la persona asociada, el grupo familiar
     * y si es jefe de hogar. Requiere permiso de actualización.
     */
    public function updateFamilyMember(
        UpdateFamilyMemberRequest $request,
        Affectation $affectation,
        FamilyMember $familyMember,
    ): JsonResponse {
        $this->authorize('update', $affectation);

        abort_if(
            $familyMember->affectation_id !== $affectation->id,
            404,
        );

        $familyMember = $this->affectationService->updateFamilyMember(
            $affectation,
            $familyMember,
            $request->validated(),
            $request->file('evidence', []),
        );

        return ApiResponse::success(FamilyMemberResource::make($familyMember->load('person')));
    }

    /**
     * Elimina un miembro familiar de una afectación.
     *
     * Desvincula a la persona del grupo familiar. La persona permanece
     * en el sistema. Requiere permiso de actualización.
     */
    public function destroyFamilyMember(Affectation $affectation, FamilyMember $familyMember): Response
    {
        $this->authorize('update', $affectation);

        abort_if(
            $familyMember->affectation_id !== $affectation->id,
            404,
        );

        $familyMember->delete();

        return response()->noContent();
    }

    /**
     * Elimina permanentemente la persona de un miembro familiar.
     *
     * Borra la persona de la base de datos junto con sus evidencias.
     * Guarda contra la eliminación de la persona principal del censo.
     * Requiere permiso de actualización.
     */
    public function destroyFamilyMemberPerson(Affectation $affectation, FamilyMember $familyMember): Response
    {
        $this->authorize('update', $affectation);

        $this->affectationService->destroyFamilyMemberPerson($affectation, $familyMember);

        return response()->noContent();
    }

    /**
     * Registra una baja (fallecido o lesionado) vinculada a una afectación.
     *
     * La causa es obligatoria para los fallecidos y debe estar activa.
     * Requiere permiso de actualización sobre la afectación.
     */
    public function storeCasualty(StoreCasualtyRequest $request, Affectation $affectation): JsonResponse
    {
        $this->authorize('update', $affectation);

        $casualty = $this->affectationService->storeCasualty($affectation, $request->validated());

        return ApiResponse::success([
            'id' => $casualty->id,
            'affectation_id' => $casualty->affectation_id,
            'person_id' => $casualty->person_id,
            'type' => $casualty->type->value,
            'cause_id' => $casualty->cause_id,
        ], [], 201);
    }
}
