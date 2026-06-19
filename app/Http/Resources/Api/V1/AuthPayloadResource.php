<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Auth payload: authenticated user + OAuth2 token pair.
 *
 * @property-read array{user: mixed, token: array<string, mixed>} $resource
 */
class AuthPayloadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource['user']),
            'token' => new TokenResource($this->resource['token']),
        ];
    }
}
