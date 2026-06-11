<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Checks if the logged-in user has the required role to access the page.
     * Accepts a comma-separated list of allowed roles from the route.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is logged in at all
        if (!session()->has('personnel_id')) {
            return redirect()->route('login')->withErrors([
                'login' => 'Please log in first.',
            ]);
        }

        // Get the current user's role from the session
        $currentRole = session('personnel_role');

        // Check if their role is inside the list of allowed roles for this route
        if (!in_array($currentRole, $roles)) {
            // If not allowed, kick them to the 403 Access Denied page
            abort(403, 'Unauthorized access. You do not have permission to view this module.');
        }

        // If they are allowed, let the request proceed normally
        return $next($request);
    }
}