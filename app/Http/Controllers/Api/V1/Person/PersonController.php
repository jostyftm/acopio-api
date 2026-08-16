<?php

namespace App\Http\Controllers\Api\V1\Person;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Person\SearchPersonRequest;
use App\Http\Requests\Api\V1\Person\UpdatePersonRequest;
use App\Http\Requests\Api\V1\Person\VerifyPersonRequest;
use App\Http\Resources\Api\V1\Person\PersonResource;
use App\Http\Resources\Api\V1\Person\PublicPersonResource;
use App\Models\Person;
use App\Services\Person\PersonService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PersonController extends Controller
{
    public function __construct(
        private readonly PersonService $personService,
    ) {}

    /**
     * Lista las personas registradas de forma paginada.
     *
     * Devuelve las personas del sistema con su estado, datos de contacto
     * y ubicación. Requiere rol de administrador u operador.
     *
     * @param  Request  $request  Consulta: `per_page` para paginación.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $people = PersonResource::collection(
            $this->personService->index()->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($people);
    }

    /**
     * Busca personas de forma pública.
     *
     * Permite buscar damnificados por término, municipio, estado o tipo de
     * documento. El número de documento se devuelve enmascarado.
     *
     * @param  SearchPersonRequest  $request  Filtros: `term`, `municipality`, `status`, `document_type` y `per_page`.
     */
    public function search(SearchPersonRequest $request): JsonResponse
    {
        $people = PublicPersonResource::collection(
            $this->personService->publicSearch($request->validated())->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($people);
    }

    /**
     * Muestra el detalle de una persona.
     *
     * Devuelve la información completa de la persona junto con su
     * verificador y sus reportes de búsqueda asociados.
     *
     * @param  Person  $person  La persona a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(Person $person): JsonResponse
    {
        $this->authorize('view', $person);

        $person->load([
            'verifiedBy',
            'searchReports',
            'municipality',
            'affectation.incidentType',
            'affectation.needs',
            'affectation.severities',
            'affectation.attachments',
            'affectation.familyMembers.person.attachments',
        ]);

        return ApiResponse::success(PersonResource::make($person));
    }

    /**
     * Actualiza los datos de una persona.
     *
     * Modifica los datos básicos, de contacto y de ubicación de la persona.
     *
     * @param  UpdatePersonRequest  $request  Datos a actualizar.
     * @param  Person  $person  La persona a actualizar.
     */
    public function update(UpdatePersonRequest $request, Person $person): JsonResponse
    {
        $person = $this->personService->update($person, $request->validated());
        $person->load([
            'municipality',
            'affectation.incidentType',
            'affectation.needs',
            'affectation.severities',
            'affectation.attachments',
            'affectation.familyMembers.person.attachments',
        ]);

        return ApiResponse::success(PersonResource::make($person));
    }

    /**
     * Verifica y/o localiza a una persona.
     *
     * Marca a la persona como verificada y, si se envían coordenadas, la
     * registra como localizada con su ubicación.
     *
     * @param  VerifyPersonRequest  $request  Datos de verificación y ubicación opcional.
     * @param  Person  $person  La persona a verificar.
     */
    public function verify(VerifyPersonRequest $request, Person $person): JsonResponse
    {
        $person = $this->personService->verify($person, $request->user());

        if ($request->filled(['latitude', 'longitude'])) {
            $person = $this->personService->locate($person, [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);
        }

        return ApiResponse::success(PersonResource::make($person->load([
            'verifiedBy',
            'municipality',
            'affectation.incidentType',
            'affectation.needs',
            'affectation.severities',
            'affectation.attachments',
            'affectation.familyMembers.person.attachments',
        ])));
    }

    /**
     * Elimina una persona.
     *
     * Elimina definitivamente el registro de la persona. Requiere permisos.
     *
     * @param  Person  $person  La persona a eliminar.
     *
     * @throws AuthorizationException
     */
    public function destroy(Person $person): Response
    {
        $this->authorize('delete', $person);

        $person->delete();

        return response()->noContent();
    }
}
