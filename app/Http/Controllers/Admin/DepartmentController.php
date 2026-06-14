<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DepartmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    private const MANUAL_OPTION = '__manual__';

    /**
     * Standard school subject departments shown in the department dropdown.
     */
    private const PRESET_DEPARTMENTS = [
        'English Department',
        'Mathematics Department',
        'Science Department',
        'Filipino Department',
        'TLE Department',
        'MAPEH Department',
        'ESP Department',
        'Araling Panlipunan Department',
    ];

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
        $departments = $this->departmentService->getDepartments(
            $request->query('search')
        );

        return view('admin.departments.index', compact('departments'));
    }

    /**
     * Shows the form for creating a department.
     */
    public function create()
    {
        return view('admin.departments.create', [
            'presetDepartments' => self::PRESET_DEPARTMENTS,
            'manualOption' => self::MANUAL_OPTION,
        ]);
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

        return view('admin.departments.edit', [
            'department' => $department,
            'presetDepartments' => self::PRESET_DEPARTMENTS,
            'manualOption' => self::MANUAL_OPTION,
        ]);
    }

    /**
     * Validates and updates an existing department.
     */
    public function update(Request $request, int $departmentId)
    {
        $validated = $this->validateDepartment($request, $departmentId);

        $this->departmentService->updateDepartment(
            $departmentId,
            $validated
        );

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
     * Resolves the selected preset or manual department name, normalizes it,
     * and validates the final value.
     */
    private function validateDepartment(
        Request $request,
        ?int $departmentId = null
    ): array {
        $allowedOptions = array_merge(
            self::PRESET_DEPARTMENTS,
            [self::MANUAL_OPTION]
        );

        $selection = $request->validate([
            'department_option' => [
                'required',
                Rule::in($allowedOptions),
            ],

            'manual_department_name' => [
                Rule::requiredIf(
                    fn (): bool =>
                        $request->input('department_option')
                        === self::MANUAL_OPTION
                ),
                'nullable',
                'string',
                'max:100',
            ],
        ], [
            'department_option.required' =>
                'Please select a department or choose manual input.',

            'department_option.in' =>
                'The selected department option is invalid.',

            'manual_department_name.required' =>
                'Please enter the department name.',

            'manual_department_name.max' =>
                'The department name must not exceed 100 characters.',
        ]);

        $departmentName =
            $selection['department_option'] === self::MANUAL_OPTION
                ? (string) ($selection['manual_department_name'] ?? '')
                : (string) $selection['department_option'];

        // Normalize spacing before uniqueness and format validation.
        $departmentName = preg_replace(
            '/\s+/u',
            ' ',
            trim($departmentName)
        );

        $validator = Validator::make([
            'department_name' => $departmentName,
        ], [
            'department_name' => [
                'required',
                'string',
                'max:100',

                // Require one letter and allow safe department-name punctuation.
                'regex:/^(?=.*\pL)[\pL\pN][\pL\pN\s\-\/._]*$/u',

                Rule::unique('department', 'department_name')
                    ->ignore($departmentId, 'department_id'),
            ],
        ], [
            'department_name.required' =>
                'The department name is required.',

            'department_name.max' =>
                'The department name must not exceed 100 characters.',

            'department_name.regex' =>
                'The department name must contain at least one letter. Only letters, numbers, spaces, hyphens (-), slashes (/), periods (.), and underscores (_) are allowed.',

            'department_name.unique' =>
                'This department already exists.',
        ]);

        $validatedDepartment = $validator->validate();

        return [
            'department_name' => $validatedDepartment['department_name'],
        ];
    }
}
