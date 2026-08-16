<?php

namespace App\Http\Controllers\Api\V1\Affectation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Affectation\StoreAffectationRequest;
use App\Http\Requests\Api\V1\Affectation\UpdateAffectationRequest;
use App\Http\Resources\Api\V1\Affectation\AffectationResource;
use App\Http\Resources\Api\V1\Person\PersonResource;
use App\Models\Affectation;
use App\Models\AffectationEvidence;
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

        $person = $this->registrationService->register(
            $request->validated(),
            markLocated: true,
            reporter: $request->user(),
        );
        $person->load(['municipality', 'affectation.needs', 'affectation.evidence']);

        return ApiResponse::success(
            PersonResource::make($person),
            ['duplicate' => ! $person->wasRecentlyCreated],
            $person->wasRecentlyCreated ? 201 : 200,
        );
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
            ->with(['person.municipality', 'needs', 'reporter', 'organization'])
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

        $affectation->load(['person.municipality', 'needs', 'evidence', 'reporter', 'organization']);

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
            'severity' => $request->input('severity'),
            'description' => $request->input('description'),
        ];

        if ($request->has('latitude') || $request->has('longitude')) {
            $latitude = $request->filled('latitude') ? (float) $request->input('latitude') : null;
            $longitude = $request->filled('longitude') ? (float) $request->input('longitude') : null;

            $data['location'] = $latitude !== null && $longitude !== null
                ? Point::makeGeodetic($latitude, $longitude)
                : null;
        }

        $affectation->update($data);

        if ($request->filled('needs')) {
            $affectation->needs()->sync($request->input('needs'));
        }

        foreach ($request->file('evidence', []) as $file) {
            $path = $file->store('evidence/affectations/'.$affectation->id, 's3');

            $affectation->evidence()->create([
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $affectation->load(['person.municipality', 'needs', 'evidence', 'reporter', 'organization']);

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

        foreach ($affectation->evidence as $evidence) {
            Storage::disk('s3')->delete($evidence->file_path);
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
    public function destroyEvidence(Request $request, Affectation $affectation, AffectationEvidence $evidence): JsonResponse
    {
        $this->authorize('update', $affectation);

        abort_if(
            $evidence->affectation_id !== $affectation->id,
            JsonResponse::HTTP_NOT_FOUND,
        );

        Storage::disk('s3')->delete($evidence->file_path);
        $evidence->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}
