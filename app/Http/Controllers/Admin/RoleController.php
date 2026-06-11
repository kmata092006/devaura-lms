<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    private RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Displays the dynamic Role Access Matrix for the admin.
     */
    public function index()
    {
        $matrix = $this->roleService->getRolePermissionsMatrix();
        $roles = $this->roleService->getRoles();

        return view('admin.roles.index', compact('matrix', 'roles'));
    }

    /**
     * Processes the form submission to grant/revoke role permissions.
     */
    public function update(Request $request)
    {
        // Expecting an array where Key = Role ID, Value = Array of Permission IDs
        $permissionsData = $request->input('permissions', []);
        $roles = $this->roleService->getRoles();

        DB::beginTransaction();

        try {
            foreach ($roles as $role) {
                // Security Check: The Admin role's permissions cannot be modified via UI
                if ($role->personnel_role_name === 'admin') {
                    continue;
                }

                $roleId = $role->personnel_role_id;
                $assignedPermissions = $permissionsData[$roleId] ?? [];

                // 1. Wipe current permissions for this specific role
                DB::table('role_permission')
                    ->where('personnel_role_id', $roleId)
                    ->delete();

                // 2. Prepare the new permission bindings
                $insertData = [];
                foreach ($assignedPermissions as $permissionId) {
                    $insertData[] = [
                        'personnel_role_id' => $roleId,
                        'permission_id' => $permissionId
                    ];
                }

                // 3. Insert the new bindings if any boxes were checked
                if (!empty($insertData)) {
                    DB::table('role_permission')->insert($insertData);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.roles.index')
                ->with('success', 'System role permissions updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->route('admin.roles.index')
                ->withErrors(['error' => 'Failed to update permissions: ' . $e->getMessage()]);
        }
    }
}