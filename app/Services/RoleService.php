<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Returns the dynamic role and permission matrix from the database.
     */
    public function getRolePermissionsMatrix(): array
    {
        // Safety net: Auto-populate permissions if the table is completely empty
        $this->ensurePermissionsExist();

        $permissions = DB::table('permission')->orderBy('permission_id')->get();
        $roles = DB::table('personnel_role')->orderBy('personnel_role_id')->get();
        $rolePermissions = DB::table('role_permission')->get();

        $matrix = [];

        // Build the skeleton matrix
        foreach ($permissions as $permission) {
            $matrix[$permission->permission_name] = [
                'permission_id' => $permission->permission_id,
                'allowed_role_ids' => []
            ];
        }

        // Fill the matrix with granted access records
        foreach ($rolePermissions as $rp) {
            $permissionName = $permissions->firstWhere('permission_id', $rp->permission_id)->permission_name;
            $matrix[$permissionName]['allowed_role_ids'][] = $rp->personnel_role_id;
        }

        return $matrix;
    }

    public function hasPermission(string $roleName, string $permissionName): bool
    {
        $role = DB::table('personnel_role')->where('personnel_role_name', $roleName)->first();
        if (!$role) return false;

        return DB::table('role_permission as rp')
            ->join('permission as p', 'rp.permission_id', '=', 'p.permission_id')
            ->where('rp.personnel_role_id', $role->personnel_role_id)
            ->where('p.permission_name', $permissionName)
            ->exists();
    }

    /**
     * Returns the list of available system roles as objects.
     */
    public function getRoles()
    {
        return DB::table('personnel_role')->orderBy('personnel_role_id')->get();
    }

    /**
     * Helper function to seed the initial permissions safely
     * so we don't end up with a blank matrix table.
     */
    private function ensurePermissionsExist(): void
    {
        if (DB::table('permission')->count() === 0) {
            $defaultModules = [
                'Dashboard', 'Attendance Logs', 'Departments', 'Library Personnel',
                'Library Members', 'Book Management', 'Borrow Book', 'Return Book',
                'Book List / Search', 'Report', 'Audit Trail', 'Role Permission'
            ];

            $inserts = array_map(fn($module) => ['permission_name' => $module], $defaultModules);
            DB::table('permission')->insert($inserts);

            // Grant all permissions to the Admin role automatically
            $adminId = DB::table('personnel_role')->where('personnel_role_name', 'admin')->value('personnel_role_id');
            
            if ($adminId) {
                $permissionIds = DB::table('permission')->pluck('permission_id');
                $adminLinks = [];
                
                foreach ($permissionIds as $pId) {
                    $adminLinks[] = [
                        'personnel_role_id' => $adminId, 
                        'permission_id' => $pId
                    ];
                }
                
                DB::table('role_permission')->insert($adminLinks);
            }
        }
    }
}