<?php

namespace App\Services;

class RoleService
{
    /**
     * Returns the current role and permission matrix.
     * Based on the defined LMS system access requirements.
     */
    public function getRolePermissionsMatrix(): array
    {
        return [
            'Dashboard'          => ['admin', 'staff', 'student staff'],
            'Attendance Logs'    => ['admin', 'staff', 'student staff'],
            'Departments'        => ['admin'],
            'Library Personnel'  => ['admin'],
            'Library Members'    => ['admin', 'staff'],
            'Book Management'    => ['admin'],
            'Borrow Book'        => ['admin', 'staff', 'student staff'],
            'Return Book'        => ['admin', 'staff', 'student staff'],
            'Book List / Search' => ['admin', 'staff', 'student staff'],
            'Report'             => ['admin', 'staff'], // Staff now has access!
            'Audit Trail'        => ['admin', 'staff'],
            'Role Permission'    => ['admin'],          // New module for Admin
        ];
    }

    /**
     * Returns the list of available system roles.
     */
    public function getRoles(): array
    {
        return ['admin', 'staff', 'student staff'];
    }
}