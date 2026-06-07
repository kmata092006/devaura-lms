<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DepartmentService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    private DepartmentService $departmentService;

    /**
     * Injects the department service used by this controller.
     */
    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    /**
     * Shows the paginated department list.
     */
    public function index(Request $request)
    {
        $departments = $this->departmentService->getDepartments($request->query('search'));

        return view('admin.departments.index', compact('departments'));
    }

    /**
     * Shows the form for creating a department.
     */
    public function create()
    {
        return view('admin.departments.create');
    }

    /**
     * Validates and stores a new department.
     */
    public function store(Request $request)
    {
        $validated = $this->validateDepartment($request);

        $this->departmentService->createDepartment($validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Shows the edit form for one department.
     */
    public function edit(int $departmentId)
    {
        $department = $this->departmentService->findDepartment($departmentId);

        return view('admin.departments.edit', compact('department'));
    }

    /**
     * Validates and updates an existing department.
     */
    public function update(Request $request, int $departmentId)
    {
        $validated = $this->validateDepartment($request, $departmentId);

        $this->departmentService->updateDepartment($departmentId, $validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Deletes a department after service-level usage checks.
     */
    public function destroy(int $departmentId)
    {
        $this->departmentService->deleteDepartment($departmentId);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    /**
     * Validates department form input and unique names.
     */
    private function validateDepartment(Request $request, ?int $departmentId = null): array
    {
        return $request->validate([
            'department_name' => [
                'required',
                'string',
                'max:100',
                'unique:department,department_name,' . $departmentId . ',department_id',
            ],
        ]);
    }
}
