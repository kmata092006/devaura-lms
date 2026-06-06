<?php

namespace App\Services;

use App\Models\LibraryPersonnel;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function attemptLogin(string $role, string $username, string $password): ?LibraryPersonnel
    {
        $personnel = LibraryPersonnel::where('username', $username)
            ->where('personnel_role', $role)
            ->first();

        if (!$personnel) {
            return null;
        }

        if (!Hash::check($password, $personnel->password_hash)) {
            return null;
        }

        return $personnel;
    }

    public function storeLoginSession(LibraryPersonnel $personnel): void
    {
        session([
            'personnel_id' => $personnel->personnel_id,
            'personnel_role' => $personnel->personnel_role,
            'username' => $personnel->username,
            'full_name' => $personnel->full_name,
        ]);
    }

    public function logout(): void
    {
        session()->flush();
    }
}