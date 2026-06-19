<?php

namespace App\Http\Controllers\Api\V1;

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
