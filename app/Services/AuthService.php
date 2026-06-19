<?php

namespace App\Services;

use App\Enums\RoleSlug;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\Passport\PasswordGrantClientCredentials;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly PasswordGrantClientCredentials $oauthClient,
    ) {}

    /**
     * Register a new user, assign the default role and issue a token.
     *
     * @param  array{name: string, email: string, password: string}  $data
     * @return array{user: User, token: array<string, mixed>}
     */
    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data): User {
            $user = $this->users->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $role = $this->roles->findBySlug(RoleSlug::User->value);

            if ($role !== null) {
                $this->users->assignRole($user, $role);
            }

            return $user;
        });

        return [
            'user' => $user->load('roles'),
            'token' => $this->issueToken($data['email'], $data['password']),
        ];
    }

    /**
     * Authenticate a user via the OAuth2 password grant.
     *
     * @param  array{email: string, password: string}  $data
     * @return array{user: User, token: array<string, mixed>}
     *
     * @throws AuthenticationException
     */
    public function login(array $data): array
    {
        $token = $this->issueToken($data['email'], $data['password']);
        $user = $this->users->findByEmail($data['email']);

        if ($user === null) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return [
            'user' => $user->load('roles'),
            'token' => $token,
        ];
    }

    /**
     * Revoke the user's current access token.
     */
    public function logout(User $user): void
    {
        $user->token()?->revoke();
    }

    /**
     * Request an access token from the password grant.
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     */
    private function issueToken(string $email, string $password): array
    {
        $response = $this->dispatchTokenRequest([
            'grant_type' => 'password',
            'client_id' => $this->oauthClient->clientId(),
            'client_secret' => $this->oauthClient->clientSecret(),
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new AuthenticationException('Invalid credentials.');
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getContent(), true) ?: [];

        return [
            'access_token' => $payload['access_token'] ?? null,
            'refresh_token' => $payload['refresh_token'] ?? null,
            'token_type' => $payload['token_type'] ?? 'Bearer',
            'expires_in' => $payload['expires_in'] ?? null,
        ];
    }

    /**
     * Dispatch an internal request to the Passport token endpoint.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function dispatchTokenRequest(array $parameters): Response
    {
        $tokenRequest = Request::create('/oauth/token', 'POST', $parameters);
        $tokenRequest->headers->set('Accept', 'application/json');

        $originalRequest = app('request');

        try {
            return app(HttpKernel::class)->handle($tokenRequest);
        } finally {
            // Restore the outer request so the parent lifecycle is unaffected.
            app()->instance('request', $originalRequest);
        }
    }
}
