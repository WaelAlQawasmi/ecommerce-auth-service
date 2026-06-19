<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiDocsEnabled
{
    /**
     * Allow access to OpenAPI docs only when explicitly enabled via config.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('api.documentation.enabled', false)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
