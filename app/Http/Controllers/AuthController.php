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
     * Authenticates a personnel account and detects its role automatically.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $username = trim($validated['username']);

        $personnel = DB::table('library_personnel as p')
            ->join(
                'personnel_role as r',
                'p.personnel_role_id',
                '=',
                'r.personnel_role_id'
            )
            ->where('p.username', $username)
            ->select(
                'p.*',
                'r.personnel_role_name'
            )
            ->first();

        if (!$personnel || !Hash::check($validated['password'], $personnel->password_hash)) {
            app(AuditTrailService::class)->record(
                'LOGIN FAILED',
                'Authentication',
                'Failed login attempt for username: ' . $username,
                $request,
                [
                    'username' => $username,
                    'personnel_role' => $personnel->personnel_role_name ?? null,
                ]
            );

            return back()
                ->withErrors([
                    'login' => 'Invalid username or password.',
                ])
                ->withInput([
                    'username' => $username,
                ]);
        }

        $hasPermissions = DB::table('role_permission')
            ->where('personnel_role_id', $personnel->personnel_role_id)
            ->exists();

        if (!$hasPermissions) {
            app(AuditTrailService::class)->record(
                'LOGIN BLOCKED',
                'Authentication',
                'Blocked login because the account role has no permissions assigned.',
                $request,
                [
                    'personnel_id' => $personnel->personnel_id,
                    'username' => $personnel->username,
                    'personnel_role' => $personnel->personnel_role_name,
                ]
            );

            return back()
                ->withErrors([
                    'access' => 'Your account role currently has no permissions assigned. Please contact an administrator.',
                ])
                ->withInput([
                    'username' => $username,
                ]);
        }

        $request->session()->regenerate();

        $request->session()->put([
            'personnel_id' => $personnel->personnel_id,
            'personnel_role_id' => $personnel->personnel_role_id,
            'username' => $personnel->username,
            'personnel_role' => $personnel->personnel_role_name,
            'first_name' => $personnel->first_name,
            'last_name' => $personnel->last_name,
        ]);

        app(AuditTrailService::class)->record(
            'LOGIN',
            'Authentication',
            'User logged in successfully as ' . ucwords($personnel->personnel_role_name) . '.',
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
     * Resets any kiosk session before sending an administrator to the login page.
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
