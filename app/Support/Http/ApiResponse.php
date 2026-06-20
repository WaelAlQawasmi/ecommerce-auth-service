<?php

namespace App\Support\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standard API response envelope used across all versioned endpoints.
 *
 * Success: { "success": true, "message": "...", "data": {...}, "meta": {...}? }
 * Error:   { "success": false, "message": "...", "errors": {...}? }
 */
final class ApiResponse
{
    /**
     * @param  mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = Response::HTTP_OK,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data instanceof JsonResource ? $data : $data;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        ?array $errors = null,
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
