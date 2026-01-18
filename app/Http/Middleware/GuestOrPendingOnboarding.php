<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestOrPendingOnboarding
{
    /**
     * Allow access to onboarding for guests and users pending onboarding.
     *
     * This middleware permits:
     * - Guests who will verify their email exists in the system during step 1
     * - Authenticated users who haven't completed onboarding
     *
     * Redirects authenticated, onboarded users to the dashboard.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Guest: allow access - they'll verify their email in step 1
        if (! $user) {
            return $next($request);
        }

        // Authenticated but pending onboarding: allow access
        if ($user->isPendingOnboarding()) {
            return $next($request);
        }

        // Authenticated and already onboarded: redirect to dashboard
        return redirect()->route('dashboard');
    }
}
