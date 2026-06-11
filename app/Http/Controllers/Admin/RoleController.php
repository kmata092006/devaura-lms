<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private RoleService $roleService;

    /**
     * Injects the role service used by the permissions page.
     */
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Displays the Role Access Matrix for the admin.
     */
    public function index()
    {
        $matrix = $this->roleService->getRolePermissionsMatrix();
        $roles = $this->roleService->getRoles();

        return view('admin.roles.index', compact('matrix', 'roles'));
    }
}