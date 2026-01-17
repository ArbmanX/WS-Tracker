<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSudoAdmin
{
    /**
     * Allows access only for users with 'sudo_admin' role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole('sudo_admin')) {
            abort(403, 'Unauthorized. Sudo admin access required.');
        }

        return $next($request);
    }
}
