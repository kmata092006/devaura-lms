<?php

namespace App\Http\Controllers;

use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (session()->has('personnel_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,staff,student staff'],
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $personnel = DB::table('library_personnel')
            ->where('username', $validated['username'])
            ->where('personnel_role', $validated['role'])
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
            'personnel_id' => $personnel->personnel_id,
            'username' => $personnel->username,
            'personnel_role' => $personnel->personnel_role,
            'first_name' => $personnel->first_name,
            'last_name' => $personnel->last_name,
        ]);

        app(AuditTrailService::class)->record(
            'LOGIN',
            'Authentication',
            'User logged in successfully.',
            $request
        );

        return redirect()->route('admin.dashboard');
    }

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
}