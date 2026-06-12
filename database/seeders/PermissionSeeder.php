<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Permissions
        $permissions = [
            'Dashboard', 'Attendance Logs', 'Departments', 'Library Personnel',
            'Library Members', 'Book Management', 'Borrow Book', 'Return Book',
            'Book List / Search', 'Report', 'Audit Trail', 'Role Permission'
        ];

        foreach ($permissions as $p) {
            DB::table('permission')->updateOrInsert(['permission_name' => $p]);
        }

        // 2. Ensure Roles exist (just in case)
        $roles = ['admin', 'staff', 'student staff'];
        foreach ($roles as $r) {
            DB::table('personnel_role')->updateOrInsert(['personnel_role_name' => $r]);
        }

        // 3. Grant all permissions to Admin automatically
        $adminId = DB::table('personnel_role')->where('personnel_role_name', 'admin')->value('personnel_role_id');
        $permissionIds = DB::table('permission')->pluck('permission_id');

        foreach ($permissionIds as $pId) {
            DB::table('role_permission')->updateOrInsert([
                'personnel_role_id' => $adminId,
                'permission_id' => $pId
            ]);
        }
    }
}