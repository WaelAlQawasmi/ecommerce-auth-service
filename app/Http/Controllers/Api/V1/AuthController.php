<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\AuthPayloadResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Support\Http\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as OpenApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group(name: 'Authentication', description: 'Sign up, sign in, and session management.', weight: 0)]
class AuthController extends ApiController
{
    public function __construct(private readonly AuthService $auth) {}

    #[Endpoint(
        operationId: 'auth.register',
        title: 'Sign up',
        description: 'Creates a new user account, assigns the default `customer` role (unless overridden by an admin), and returns an OAuth2 access token via the password grant. No authentication required for customer sign-up.',
    )]
    #[OpenApiResponse(
        status: 201,
        description: 'Account created and access token issued.',
        examples: [[
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'roles' => [
                        ['id' => 3, 'name' => 'Customer', 'slug' => 'customer', 'description' => 'Standard customer account'],
                    ],
                    'created_at' => '2026-06-20T12:00:00+00:00',
                    'updated_at' => '2026-06-20T12:00:00+00:00',
                ],
                'token' => [
                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                    'refresh_token' => 'def50200a1b2c3...',
                    'token_type' => 'Bearer',
                    'expires_in' => 1296000,
                ],
            ],
        ]],
    )]
    #[OpenApiResponse(status: 422, description: 'Validation failed (duplicate email, weak password, etc.).')]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return ApiResponse::success(
            data: new AuthPayloadResource($result),
            message: 'Registration successful.',
            status: Response::HTTP_CREATED,
        );
    }

    #[Endpoint(
        operationId: 'auth.login',
        title: 'Log in',
        description: 'Authenticates with email and password and returns the user profile plus an OAuth2 access/refresh token pair. No prior authentication required.',
    )]
    #[OpenApiResponse(
        status: 200,
        description: 'Authenticated successfully.',
        examples: [[
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'roles' => [
                        ['id' => 3, 'name' => 'Customer', 'slug' => 'customer', 'description' => 'Standard customer account'],
                    ],
                    'created_at' => '2026-06-20T12:00:00+00:00',
                    'updated_at' => '2026-06-20T12:00:00+00:00',
                ],
                'token' => [
                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                    'refresh_token' => 'def50200a1b2c3...',
                    'token_type' => 'Bearer',
                    'expires_in' => 1296000,
                ],
            ],
        ]],
    )]
    #[OpenApiResponse(status: 401, description: 'Invalid email or password.', examples: [[
        'success' => false,
        'message' => 'Unauthenticated.',
    ]])]
    #[OpenApiResponse(status: 422, description: 'Validation failed (missing or invalid fields).')]
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
