<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Receives the service that reads and writes role permissions.
     */
    public function __construct(private RoleService $roleService)
    {
    }

    /**
     * Displays the role and permission matrix for the administrator.
     */
    public function index()
    {
        $matrix = $this->roleService->getRolePermissionsMatrix();
        $roles = $this->roleService->getRoles();

        return view('admin.roles.index', compact('matrix', 'roles'));
    }

    /**
     * Grants or revokes Staff and Student Staff module permissions.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => [
                'integer',
                'distinct',
                'exists:permission,permission_id',
            ],
        ]);

        $permissionsData = $validated['permissions'] ?? [];
        $roles = $this->roleService->getRoles();

        $permissionIdsByName = DB::table('permission')
            ->pluck('permission_id', 'permission_name');

        $dashboardPermissionId = $permissionIdsByName->get('Dashboard');
        $rolePermissionId = $permissionIdsByName->get('Role Permission');
        $validPermissionIds = $permissionIdsByName
            ->values()
            ->map(static fn ($id): int => (int) $id)
            ->all();

        // Replace each non-admin role's module permissions in one transaction.
        DB::transaction(function () use (
            $roles,
            $permissionsData,
            $dashboardPermissionId,
            $rolePermissionId,
            $validPermissionIds
        ): void {
            foreach ($roles as $role) {
                $roleName = strtolower(trim($role->personnel_role_name));

                if ($roleName === 'admin') {
                    continue;
                }

                $roleId = (int) $role->personnel_role_id;
                $assignedPermissions = array_map(
                    'intval',
                    $permissionsData[$roleId] ?? []
                );

                $assignedPermissions = array_values(array_unique(array_filter(
                    $assignedPermissions,
                    static fn (int $permissionId): bool => in_array(
                        $permissionId,
                        $validPermissionIds,
                        true
                    )
                )));

                if ($dashboardPermissionId) {
                    $assignedPermissions[] = (int) $dashboardPermissionId;
                }

                // Keep Role Permission available only to admin accounts.
                if ($rolePermissionId) {
                    $assignedPermissions = array_values(array_filter(
                        $assignedPermissions,
                        static fn (int $permissionId): bool => $permissionId !== (int) $rolePermissionId
                    ));
                }

                $assignedPermissions = array_values(array_unique($assignedPermissions));

                DB::table('role_permission')
                    ->where('personnel_role_id', $roleId)
                    ->delete();

                if ($assignedPermissions === []) {
                    continue;
                }

                $rows = array_map(
                    static fn (int $permissionId): array => [
                        'personnel_role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    $assignedPermissions
                );

                DB::table('role_permission')->insert($rows);
            }
        });

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'System role permissions updated successfully.');
    }
}
