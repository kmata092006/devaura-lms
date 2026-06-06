<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function showLogin()
    {
        if (session()->has('personnel_id')) {
            if (session('personnel_role') === 'admin') {
                return redirect()->route('admin.dashboard');
            }
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

        $personnel = $this->authService->attemptLogin(
            $validated['role'],
            $validated['username'],
            $validated['password']
        );

        if (!$personnel) {
            return back()
                ->withErrors([
                    'login' => 'Invalid role, username, or password.',
                ])
                ->withInput();
        }

        $this->authService->storeLoginSession($personnel);

        if ($personnel->personnel_role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        $this->authService->logout();

        return back()
            ->withErrors([
                'login' => 'Login verified, but only the Admin dashboard is implemented for now.',
            ])
            ->withInput();
    }

    public function logout()
    {
        $this->authService->logout();

        return redirect()->route('login')->with('success', 'You have logged out successfully.');
    }
}