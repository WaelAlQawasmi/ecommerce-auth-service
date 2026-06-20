<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\AssignRoleRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @tags User Roles
 */
class UserRoleController extends ApiController
{
    public function __construct(private readonly UserService $users) {}

    /**
     * Assign a role to a user.
     *
     * Staff may assign the customer role; only administrators may assign admin or support roles.
     * Authorized by {@see \App\Policies\UserPolicy::assignRole}.
     */
    public function store(AssignRoleRequest $request, User $user): JsonResponse
    {
        $role = $request->validated('role');

        $this->authorize('assignRole', [$user, $role]);

        $updated = $this->users->assignRole($user, $role);

        return ApiResponse::success(
            data: new UserResource($updated),
            message: 'Role assigned successfully.',
        );
    }
}
