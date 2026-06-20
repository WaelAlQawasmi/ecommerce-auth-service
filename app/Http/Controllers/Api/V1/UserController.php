<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\ListUsersRequest;
use App\Http\Requests\Api\V1\SearchUsersRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @tags Users
 */
class UserController extends ApiController
{
    public function __construct(private readonly UserService $users) {}

    /**
     * List users with pagination (admin and support only).
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        $paginator = $this->users->paginate($perPage);

        return ApiResponse::success(
            data: UserResource::collection($paginator->items()),
            meta: [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        );
    }

    /**
     * Search users by email (admin and support only).
     */
    public function search(SearchUsersRequest $request): JsonResponse
    {
        $users = $this->users->searchByEmail($request->validated('email'));

        return ApiResponse::success(
            data: UserResource::collection($users),
            meta: [
                'count' => $users->count(),
            ],
        );
    }

    /**
     * Get total user count (admin and support only).
     */
    public function count(): JsonResponse
    {
        return ApiResponse::success(
            data: [
                'count' => $this->users->count(),
            ],
        );
    }

    /**
     * Soft delete a user account.
     *
     * Users may delete their own account; administrators may delete any account.
     * Authorized by {@see \App\Policies\UserPolicy::delete}.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->users->deleteAccount($user);

        return ApiResponse::success(message: 'Account deleted successfully.');
    }
}
