<?php

namespace App\Services;

use App\Models\LibraryPersonnel;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Validates a personnel login against role, username, and password.
     */
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

    /**
     * Stores the authenticated personnel identity in the session.
     */
    public function storeLoginSession(LibraryPersonnel $personnel): void
    {
        session([
            'personnel_id' => $personnel->personnel_id,
            'personnel_role' => $personnel->personnel_role,
            'username' => $personnel->username,
            'full_name' => $personnel->full_name,
        ]);
    }

    /**
     * Clears all session data for logout.
     */
    public function logout(): void
    {
        session()->flush();
    }
}
