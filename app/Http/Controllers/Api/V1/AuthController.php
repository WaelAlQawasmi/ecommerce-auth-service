<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\AuthPayloadResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags Authentication
 */
class AuthController extends ApiController
{
    public function __construct(private readonly AuthService $auth) {}

    /**
     * Register a new user account.
     *
     * Creates the user, assigns the default "user" role, and returns an OAuth2
     * access token via the password grant.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return ApiResponse::success(
            data: new AuthPayloadResource($result),
            message: 'Registration successful.',
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Sign in with email and password.
     *
     * Returns the authenticated user profile and an OAuth2 token pair.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->validated());

        return ApiResponse::success(
            data: new AuthPayloadResource($result),
            message: 'Login successful.',
        );
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return ApiResponse::success(message: 'Logged out successfully.');
    }

    /**
     * Get the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            data: new UserResource($request->user()->load('roles')),
        );
    }
}
