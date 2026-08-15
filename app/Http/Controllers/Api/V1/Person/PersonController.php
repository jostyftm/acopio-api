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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PersonController extends Controller
{
    public function __construct(
        private readonly PersonService $personService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Person::class);

        $people = PersonResource::collection(
            $this->personService->index()->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($people);
    }

    public function search(SearchPersonRequest $request): JsonResponse
    {
        $people = PublicPersonResource::collection(
            $this->personService->publicSearch($request->validated())->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($people);
    }

    public function show(Person $person): JsonResponse
    {
        $this->authorize('view', $person);

        $person->load(['verifiedBy', 'searchReports']);

        return ApiResponse::success(PersonResource::make($person));
    }

    public function update(UpdatePersonRequest $request, Person $person): JsonResponse
    {
        $person = $this->personService->update($person, $request->validated());

        return ApiResponse::success(PersonResource::make($person));
    }

    public function verify(VerifyPersonRequest $request, Person $person): JsonResponse
    {
        $person = $this->personService->verify($person, $request->user());

        if ($request->filled(['latitude', 'longitude'])) {
            $person = $this->personService->locate($person, [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ]);
        }

        return ApiResponse::success(PersonResource::make($person->load('verifiedBy')));
    }

    public function destroy(Person $person): Response
    {
        $this->authorize('delete', $person);

        $person->delete();

        return response()->noContent();
    }
}
