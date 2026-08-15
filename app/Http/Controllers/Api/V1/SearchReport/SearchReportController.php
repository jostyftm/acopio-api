<?php

namespace App\Http\Controllers\Api\V1\SearchReport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchReport\StoreSearchReportRequest;
use App\Http\Requests\Api\V1\SearchReport\UpdateSearchReportRequest;
use App\Http\Resources\Api\V1\SearchReport\SearchReportResource;
use App\Models\Person;
use App\Models\SearchReport;
use App\Services\SearchReport\SearchReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class SearchReportController extends Controller
{
    public function __construct(
        private readonly SearchReportService $searchReportService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SearchReport::class);

        $reports = SearchReportResource::collection(
            QueryBuilder::for(SearchReport::class)
                ->with(['person', 'handledBy'])
                ->allowedFilters('status', 'municipality', 'document_number')
                ->defaultSort('-created_at')
                ->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($reports);
    }

    public function store(StoreSearchReportRequest $request): JsonResponse
    {
        $report = $this->searchReportService->create($request->validated());

        return ApiResponse::success(
            SearchReportResource::make($report),
            null,
            201,
        );
    }

    public function show(SearchReport $searchReport): JsonResponse
    {
        $this->authorize('view', $searchReport);

        $searchReport->load(['person', 'handledBy']);

        return ApiResponse::success(SearchReportResource::make($searchReport));
    }

    public function update(UpdateSearchReportRequest $request, SearchReport $searchReport): JsonResponse
    {
        if ($request->input('status') === 'found') {
            $person = $request->input('person_id')
                ? Person::find($request->input('person_id'))
                : null;

            $searchReport = $this->searchReportService->markFound($searchReport, $person, $request->user());
        } else {
            $searchReport->update($request->validated());
            $searchReport = $searchReport->fresh();
        }

        return ApiResponse::success(SearchReportResource::make($searchReport->load(['person', 'handledBy'])));
    }
}
