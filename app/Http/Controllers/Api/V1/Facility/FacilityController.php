<?php

namespace App\Http\Controllers\Api\V1\Facility;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Facility\StoreFacilityPhotosRequest;
use App\Http\Requests\Api\V1\Facility\StoreFacilityRequest;
use App\Http\Requests\Api\V1\Facility\UpdateFacilityRequest;
use App\Http\Resources\Api\V1\Facility\FacilityResource;
use App\Models\Facility;
use App\Services\Facility\FacilityService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacilityController extends Controller
{
    public function __construct(
        private readonly FacilityService $facilityService,
    ) {}

    /**
     * Lista los centros de acopio y albergues de forma paginada.
     *
     * Un administrador de organización solo ve los espacios de su organización.
     *
     * @param  Request  $request  Consulta: filtros `name`, `status`, `organization_id`, `facility_type_id`, `municipality_id` y `per_page`.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Facility::class);

        $facilities = FacilityResource::collection(
            $this->facilityService->index(
                $request->user(),
                $request->only(['name', 'status', 'organization_id', 'facility_type_id', 'municipality_id']),
                $request->integer('per_page', 15),
            ),
        );

        return ApiResponse::success($facilities);
    }

    /**
     * Crea un nuevo centro de acopio o albergue.
     *
     * @param  StoreFacilityRequest  $request  Datos del espacio.
     */
    public function store(StoreFacilityRequest $request): JsonResponse
    {
        $this->authorize('create', Facility::class);

        $facility = $this->facilityService->create($request->validated());

        return ApiResponse::success(
            FacilityResource::make($facility),
            null,
            201,
        );
    }

    /**
     * Muestra el detalle de un centro de acopio o albergue.
     *
     * @param  Facility  $facility  El espacio a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(Facility $facility): JsonResponse
    {
        $this->authorize('view', $facility);

        $facility = $this->facilityService->show($facility);

        return ApiResponse::success(FacilityResource::make($facility));
    }

    /**
     * Actualiza un centro de acopio o albergue.
     *
     * @param  UpdateFacilityRequest  $request  Datos a actualizar.
     * @param  Facility  $facility  El espacio a actualizar.
     */
    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $this->authorize('update', $facility);

        $facility = $this->facilityService->update($facility, $request->validated());

        return ApiResponse::success(FacilityResource::make($facility));
    }

    /**
     * Elimina un centro de acopio o albergue.
     *
     * @param  Facility  $facility  El espacio a eliminar.
     */
    public function destroy(Facility $facility): Response
    {
        $this->authorize('delete', $facility);

        $facility->delete();

        return response()->noContent();
    }

    /**
     * Sube fotos de evidencia de un centro de acopio o albergue.
     *
     * @param  StoreFacilityPhotosRequest  $request  Archivos de imagen.
     * @param  Facility  $facility  El espacio al que pertenecen las fotos.
     */
    public function storePhotos(StoreFacilityPhotosRequest $request, Facility $facility): JsonResponse
    {
        $photos = $this->facilityService->uploadPhotos($facility, $request->file('photos', []));

        return ApiResponse::success($photos);
    }
}
