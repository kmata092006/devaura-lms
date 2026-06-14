<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Creates the permission catalog and assigns sensible default access.
     * Existing Staff or Student Staff customizations are preserved once
     * those roles already have at least one permission record.
     */
    public function run(): void
    {
        $permissions = [
            'Dashboard',
            'Attendance Logs',
            'Departments',
            'Library Personnel',
            'Library Members',
            'Book Management',
            'Borrow Book',
            'Return Book',
            'Book List / Search',
            'Report',
            'Audit Trail',
            'Role Permission',
        ];

        $roles = [
            'admin',
            'staff',
            'student staff',
        ];

        $defaultPermissions = [
            'staff' => [
                'Dashboard',
                'Attendance Logs',
                'Borrow Book',
                'Return Book',
                'Book List / Search',
                'Audit Trail',
            ],
            'student staff' => [
                'Dashboard',
                'Attendance Logs',
                'Borrow Book',
                'Return Book',
                'Book List / Search',
            ],
        ];

        DB::transaction(function () use ($permissions, $roles, $defaultPermissions): void {
            foreach ($permissions as $permissionName) {
                DB::table('permission')->updateOrInsert([
                    'permission_name' => $permissionName,
                ]);
            }

            foreach ($roles as $roleName) {
                DB::table('personnel_role')->updateOrInsert([
                    'personnel_role_name' => $roleName,
                ]);
            }

            $roleIds = DB::table('personnel_role')
                ->whereIn('personnel_role_name', $roles)
                ->pluck('personnel_role_id', 'personnel_role_name');

            $permissionIds = DB::table('permission')
                ->whereIn('permission_name', $permissions)
                ->pluck('permission_id', 'permission_name');

            $adminId = $roleIds->get('admin');

            if ($adminId) {
                foreach ($permissionIds as $permissionId) {
                    DB::table('role_permission')->updateOrInsert([
                        'personnel_role_id' => $adminId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            foreach ($defaultPermissions as $roleName => $permissionNames) {
                $roleId = $roleIds->get($roleName);

                if (!$roleId) {
                    continue;
                }

                $roleAlreadyConfigured = DB::table('role_permission')
                    ->where('personnel_role_id', $roleId)
                    ->exists();

                if ($roleAlreadyConfigured) {
                    continue;
                }

                $rows = [];

                foreach ($permissionNames as $permissionName) {
                    $permissionId = $permissionIds->get($permissionName);

                    if ($permissionId) {
                        $rows[] = [
                            'personnel_role_id' => $roleId,
                            'permission_id' => $permissionId,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('role_permission')->insert($rows);
                }
            }
        });
    }
}
