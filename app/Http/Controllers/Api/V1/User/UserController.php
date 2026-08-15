<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreUserRequest;
use App\Http\Requests\Api\V1\User\UpdateUserRequest;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = UserResource::collection(
            QueryBuilder::for(User::class)
                ->allowedFilters('email')
                ->defaultSort('-created_at')
                ->cursorPaginate($request->integer('per_page', 15)),
        );

        return ApiResponse::success($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return ApiResponse::success(UserResource::make($user), null, 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return ApiResponse::success(UserResource::make($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->userService->update($user, $request->validated());

        return ApiResponse::success(UserResource::make($user));
    }

    public function destroy(User $user): Response
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }
}
