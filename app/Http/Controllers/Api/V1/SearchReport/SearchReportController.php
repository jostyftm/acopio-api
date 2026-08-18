<?php

namespace App\Http\Controllers\Api\V1\SearchReport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchReport\StoreSearchReportRequest;
use App\Http\Requests\Api\V1\SearchReport\UpdateSearchReportRequest;
use App\Http\Resources\Api\V1\SearchReport\SearchReportResource;
use App\Models\SearchReport;
use App\Services\SearchReport\SearchReportService;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchReportController extends Controller
{
    public function __construct(
        private readonly SearchReportService $searchReportService,
    ) {}

    /**
     * Lista los reportes de búsqueda de forma paginada.
     *
     * Devuelve los reportes de personas desaparecidas con sus filtros y
     * la relación a la persona y al funcionario que los atendió.
     *
     * @param  Request  $request  Consulta: filtros `status`, `municipality`, `document_number` y `per_page`.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SearchReport::class);

        $reports = SearchReportResource::collection(
            $this->searchReportService->index(
                $request->only(['status', 'municipality', 'document_number']),
                $request->integer('per_page', 15),
            ),
        );

        return ApiResponse::success($reports);
    }

    /**
     * Crea un reporte de búsqueda.
     *
     * Registra la solicitud de localización de una persona desaparecida.
     *
     * @param  StoreSearchReportRequest  $request  Datos del reporte.
     */
    public function store(StoreSearchReportRequest $request): JsonResponse
    {
        $report = $this->searchReportService->create($request->validated());

        return ApiResponse::success(
            SearchReportResource::make($report),
            null,
            201,
        );
    }

    /**
     * Muestra el detalle de un reporte de búsqueda.
     *
     * Devuelve la información del reporte junto con la persona relacionada
     * y el funcionario que lo atendió.
     *
     * @param  SearchReport  $searchReport  El reporte a consultar.
     *
     * @throws AuthorizationException
     */
    public function show(SearchReport $searchReport): JsonResponse
    {
        $this->authorize('view', $searchReport);

        $searchReport = $this->searchReportService->show($searchReport);

        return ApiResponse::success(SearchReportResource::make($searchReport));
    }

    /**
     * Actualiza o resuelve un reporte de búsqueda.
     *
     * Si el estado cambia a `found`, asocia el reporte a la persona
     * encontrada y registra la ubicación; de lo contrario actualiza los
     * datos del reporte.
     *
     * @param  UpdateSearchReportRequest  $request  Datos a actualizar.
     * @param  SearchReport  $searchReport  El reporte a actualizar.
     */
    public function update(UpdateSearchReportRequest $request, SearchReport $searchReport): JsonResponse
    {
        $searchReport = $this->searchReportService->update(
            $searchReport,
            $request->validated(),
            $request->user(),
        );

        return ApiResponse::success(SearchReportResource::make($searchReport));
    }
}
