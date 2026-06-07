<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Allows only logged-in admin personnel to access protected routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('personnel_id')) {
            return redirect()->route('login')->withErrors([
                'login' => 'Please log in first.',
            ]);
        }

        if (session('personnel_role') !== 'admin') {
            abort(403, 'Unauthorized access. Admin only.');
        }

        return $next($request);
    }
}
