<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Organization\StoreOrganizationRequest;
use App\Http\Requests\Api\V1\Organization\StoreOrganizationUserRequest;
use App\Http\Requests\Api\V1\Organization\UpdateOrganizationCoverageRequest;
use App\Http\Requests\Api\V1\Organization\UpdateOrganizationRequest;
use App\Http\Resources\Api\V1\Organization\OrganizationResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\Organization;
use App\Services\Organization\OrganizationService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    /**
     * Lista las organizaciones de forma paginada.
     *
     * @param  Request  $request  Consulta: filtros `name`, `status`, `organization_type_id` y `per_page`.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = OrganizationResource::collection(
            QueryBuilder::for(Organization::class)
                ->with('type', 'municipality')
                ->withCount('users')
                ->allowedFilters(
                    AllowedFilter::partial('name'),
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('organization_type_id'),
                )
                ->defaultSort('name')
                ->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($organizations);
    }

    /**
     * Crea una nueva organización.
     *
     * @param  StoreOrganizationRequest  $request  Datos de la organización.
     */
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $this->authorize('create', Organization::class);

        $organization = $this->organizationService->create($request->validated());

        return ApiResponse::success(
            OrganizationResource::make($organization->load('type', 'municipality')->loadCount('users')),
            null,
            201,
        );
    }

    /**
     * Muestra el detalle de una organización.
     *
     * @param  Organization  $organization  La organización a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return ApiResponse::success(
            OrganizationResource::make($organization->load('type', 'municipality', 'coverageZones.municipality')->loadCount('users')),
        );
    }

    /**
     * Actualiza una organización.
     *
     * @param  UpdateOrganizationRequest  $request  Datos a actualizar.
     * @param  Organization  $organization  La organización a actualizar.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $organization = $this->organizationService->update($organization, $request->validated());

        return ApiResponse::success(
            OrganizationResource::make($organization->load('type', 'municipality', 'coverageZones.municipality')->loadCount('users')),
        );
    }

    /**
     * Asigna las zonas de cobertura de una organización.
     *
     * @param  UpdateOrganizationCoverageRequest  $request  Identificadores de las zonas a asignar.
     * @param  Organization  $organization  La organización a actualizar.
     */
    public function updateCoverage(UpdateOrganizationCoverageRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $organization->coverageZones()->sync($request->validated('coverage_zone_ids'));

        return ApiResponse::success(
            OrganizationResource::make($organization->load('type', 'coverageZones.municipality')->loadCount('users')),
        );
    }

    /**
     * Elimina una organización.
     *
     * @param  Organization  $organization  La organización a eliminar.
     */
    public function destroy(Organization $organization): Response
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->noContent();
    }

    /**
     * Crea un usuario dentro de una organización.
     *
     * Registra un usuario con rol "operator" en la organización especificada.
     *
     * @param  StoreOrganizationUserRequest  $request  Datos del usuario.
     * @param  Organization  $organization  La organización a la que pertenece el usuario.
     */
    public function storeUser(Organization $organization, StoreOrganizationUserRequest $request): JsonResponse
    {
        $user = $this->organizationService->createUser($organization, $request->validated());

        return ApiResponse::success(UserResource::make($user->load('organization')), null, 201);
    }
}
