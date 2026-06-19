<?php

namespace App\Http\Middleware;

use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Ensure the authenticated user has at least one of the given roles.
     *
     * Usage: ->middleware('role:admin') or ->middleware('role:admin,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasAnyRole($roles)) {
            return ApiResponse::error(
                'This action is unauthorized.',
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
