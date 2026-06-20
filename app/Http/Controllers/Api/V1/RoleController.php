<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\RoleResource;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * @tags Roles
 */
class RoleController extends ApiController
{
    public function __construct(private readonly RoleRepositoryInterface $roles) {}

    /**
     * List all available roles (admin only).
     */
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            data: RoleResource::collection($this->roles->all()),
        );
    }
}
