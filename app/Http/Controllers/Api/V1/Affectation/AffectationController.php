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
use App\Models\AffectationStatus;
use App\Models\Attachment;
use App\Services\Affectation\AffectationReportService;
use App\Services\Person\PersonService;
use App\Services\Registration\RegistrationService;
use App\Support\ApiResponse;
use Clickbar\Magellan\Data\Geometries\Point;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AffectationController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly AffectationReportService $affectationReportService,
        private readonly PersonService $personService,
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
        $person->load(['municipality', 'affectation.needs', 'affectation.incidentType', 'affectation.severities', 'affectation.propertyTypes', 'affectation.attachments', 'affectation.familyMembers.person.attachments']);

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

        $affectation->load(['needs', 'incidentType', 'severities', 'propertyTypes', 'attachments', 'reporter', 'organization', 'status']);

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

        $hasLocation = $request->filled(['latitude', 'longitude']);
        $statusCode = $hasLocation ? 'located' : 'verified';
        $status = AffectationStatus::query()->where('code', $statusCode)->firstOrFail();

        $affectation->update([
            'status_id' => $status->id,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'located_at' => $hasLocation ? now() : null,
        ]);

        if ($affectation->person !== null) {
            $person = $this->personService->verify($affectation->person, $request->user());

            if ($hasLocation) {
                $this->personService->locate($person, [
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                ]);
            }
        }

        if ($hasLocation) {
            $affectation->update([
                'location' => Point::makeGeodetic(
                    (float) $request->input('latitude'),
                    (float) $request->input('longitude'),
                ),
            ]);
        }

        $affectation->load(['person.municipality', 'needs', 'incidentType', 'severities', 'propertyTypes', 'attachments', 'reporter', 'organization', 'status', 'familyMembers.person.attachments']);

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

        $query = Affectation::query()
            ->with(['person.municipality', 'needs', 'incidentType', 'severities', 'propertyTypes', 'reporter', 'organization', 'status', 'verifiedBy'])
            ->latest('id');

        if (! $request->user()->hasRole('admin', 'api')) {
            $query->where(function ($builder) use ($request) {
                if ($request->user()->organization_id !== null) {
                    $builder->where('organization_id', $request->user()->organization_id);
                }

                $builder->orWhere('reported_by', $request->user()->id);
            });
        }

        $affectations = AffectationResource::collection($query->cursorPaginate($request->integer('per_page', 15)));

        return ApiResponse::success($affectations);
    }

    /**
     * Muestra el detalle de una afectación con la información de la persona.
     *
     * Requiere permiso de visualización del módulo de afectaciones.
     */
    public function show(Request $request, Affectation $affectation): JsonResponse
    {
        $this->authorize('view', $affectation);

        $affectation->load(['person.municipality', 'needs', 'incidentType', 'severities', 'propertyTypes', 'attachments', 'reporter', 'organization', 'status', 'verifiedBy', 'familyMembers.person.attachments']);

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

        $data = [
            'description' => $request->input('description'),
        ];

        if ($request->has('incident_type_id')) {
            $data['incident_type_id'] = $request->input('incident_type_id');
        }

        if ($request->has('latitude') || $request->has('longitude')) {
            $latitude = $request->filled('latitude') ? (float) $request->input('latitude') : null;
            $longitude = $request->filled('longitude') ? (float) $request->input('longitude') : null;

            $data['location'] = $latitude !== null && $longitude !== null
                ? Point::makeGeodetic($latitude, $longitude)
                : null;
        }

        $affectation->update($data);

        if ($request->has('severities')) {
            $affectation->severities()->sync($request->input('severities', []));
        }

        if ($request->has('property_types')) {
            $affectation->propertyTypes()->sync($request->input('property_types', []));
        }

        if ($request->filled('needs')) {
            $affectation->needs()->sync($request->input('needs'));
        }

        foreach ($request->file('evidence', []) as $file) {
            $path = $file->store('evidence/affectations/'.$affectation->id, 's3');

            $affectation->attachments()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $affectation->load(['person.municipality', 'needs', 'incidentType', 'severities', 'propertyTypes', 'attachments', 'reporter', 'organization', 'status', 'verifiedBy', 'familyMembers.person.attachments']);

        return ApiResponse::success(AffectationResource::make($affectation));
    }

    /**
     * Elimina una afectación.
     *
     * Borra la afectación y sus evidencias del almacenamiento. La persona
     * permanece en el sistema. Requiere permiso de eliminación del módulo
     * de afectaciones.
     */
    public function destroy(Request $request, Affectation $affectation): Response
    {
        $this->authorize('delete', $affectation);

        foreach ($affectation->attachments as $attachment) {
            Storage::disk('s3')->delete($attachment->file_path);
        }

        $affectation->delete();

        return response()->noContent();
    }

    /**
     * Elimina una evidencia subida a la afectación.
     *
     * Quita el archivo del almacenamiento y registra el borrado. Requiere
     * permiso de actualización del módulo de afectaciones.
     */
    public function destroyEvidence(Request $request, Affectation $affectation, Attachment $evidence): JsonResponse
    {
        $this->authorize('update', $affectation);

        abort_if(
            $evidence->attachable_type !== Affectation::class
                || $evidence->attachable_id !== $affectation->id,
            JsonResponse::HTTP_NOT_FOUND,
        );

        Storage::disk('s3')->delete($evidence->file_path);
        $evidence->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
