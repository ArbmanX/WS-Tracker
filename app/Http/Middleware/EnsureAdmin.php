<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Allows access for users with 'sudo_admin' or 'admin' roles.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasAnyRole(['sudo_admin', 'admin'])) {
            abort(403, 'Unauthorized. Admin access required.');
        }

        return $next($request);
    }
}
