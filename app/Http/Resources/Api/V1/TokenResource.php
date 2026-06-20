<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * OAuth2 token payload returned after login / register.
 *
 * @property-read array<string, mixed> $resource
 */
class TokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource['access_token'] ?? null,
            'refresh_token' => $this->resource['refresh_token'] ?? null,
            'token_type' => $this->resource['token_type'] ?? 'Bearer',
            'expires_in' => $this->resource['expires_in'] ?? null,
        ];
    }
}
