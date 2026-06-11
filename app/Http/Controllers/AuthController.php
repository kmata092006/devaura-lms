<?php

namespace App\Http\Controllers;

use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Shows the login form or redirects an already logged-in user.
     */
    public function showLoginForm()
    {
        if (session()->has('personnel_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Validates login credentials, starts the session, and records the attempt.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,staff,student staff'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $personnel = DB::table('library_personnel as p')
            ->join('personnel_role as r', 'p.personnel_role_id', '=', 'r.personnel_role_id')
            ->where('p.username', $validated['username'])
            ->where('r.personnel_role_name', $validated['role'])
            ->select('p.*', 'r.personnel_role_name') // Fetch role name for session
            ->first();

        if (!$personnel || !Hash::check($validated['password'], $personnel->password_hash)) {
            app(AuditTrailService::class)->record(
                'LOGIN FAILED',
                'Authentication',
                'Failed login attempt for username: ' . $validated['username'] . ', role: ' . $validated['role'],
                $request,
                [
                    'username' => $validated['username'],
                    'personnel_role' => $validated['role'],
                ]
            );

            return back()
                ->withErrors([
                    'login' => 'Invalid username, password, or role.',
                ])
                ->withInput($request->except('password'));
        }

        session([
            'personnel_id'      => $personnel->personnel_id,
            'username'          => $personnel->username,
            'personnel_role'    => $personnel->personnel_role_name,
            'first_name'        => $personnel->first_name,
            'last_name'         => $personnel->last_name,
        ]);

        app(AuditTrailService::class)->record(
            'LOGIN',
            'Authentication',
            'User logged in successfully.',
            $request
        );

        return redirect()->route('admin.dashboard');
    }

    /**
     * Records logout and clears the current session safely.
     */
    public function logout(Request $request)
    {
        app(AuditTrailService::class)->record(
            'LOGOUT',
            'Authentication',
            'User logged out successfully.',
            $request
        );

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Resets any kiosk session before sending an admin to the login page.
     */
    public function kioskAdminLogin(Request $request)
    {
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('info', 'Please sign in to access the admin system.');
    }
}
