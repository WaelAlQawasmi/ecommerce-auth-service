<?php

namespace App\Http\Middleware;

use App\Enums\RoleSlug;
use App\Support\Http\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminForNonCustomerRole
{
    /**
     * When a request assigns a non-customer role, the authenticated user must be an admin.
     *
     * Usage: ->middleware('admin_for_non_customer_role')
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->input('role');

        if ($role === null || $role === RoleSlug::Customer->value) {
            return $next($request);
        }

        $user = $request->user();

        if ($user === null || ! $user->hasRole(RoleSlug::Admin->value)) {
            return ApiResponse::error(
                'Only administrators can assign non-customer roles.',
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
