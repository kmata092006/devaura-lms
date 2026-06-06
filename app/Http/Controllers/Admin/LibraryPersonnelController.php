<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DepartmentService;
use App\Services\LibraryPersonnelService;
use Illuminate\Http\Request;

class LibraryPersonnelController extends Controller
{
    private LibraryPersonnelService $libraryPersonnelService;
    private DepartmentService $departmentService;

    public function __construct(
        LibraryPersonnelService $libraryPersonnelService,
        DepartmentService $departmentService
    ) {
        $this->libraryPersonnelService = $libraryPersonnelService;
        $this->departmentService = $departmentService;
    }

    public function index(Request $request)
    {
        $personnel = $this->libraryPersonnelService->getPersonnel(
            $request->query('search'),
            $request->query('role'),
            $request->query('department_id')
        );

        $roles = $this->libraryPersonnelService->roleOptions();
        $departments = $this->departmentService->getDepartmentOptions();

        return view('admin.personnel.index', compact('personnel', 'roles', 'departments'));
    }

    public function create()
    {
        $roles = $this->libraryPersonnelService->roleOptions();
        $shiftDays = $this->libraryPersonnelService->shiftDayOptions();
        $shiftPeriods = $this->libraryPersonnelService->shiftPeriodOptions();
        $departments = $this->departmentService->getDepartmentOptions();

        return view('admin.personnel.create', compact('roles', 'shiftDays', 'shiftPeriods', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePersonnel($request);

        $this->libraryPersonnelService->createPersonnel($validated);

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel created successfully.');
    }

    public function edit(int $personnelId)
    {
        $personnel = $this->libraryPersonnelService->findPersonnel($personnelId);
        $roles = $this->libraryPersonnelService->roleOptions();
        $shiftDays = $this->libraryPersonnelService->shiftDayOptions();
        $shiftPeriods = $this->libraryPersonnelService->shiftPeriodOptions();
        $departments = $this->departmentService->getDepartmentOptions();

        return view('admin.personnel.edit', compact('personnel', 'roles', 'shiftDays', 'shiftPeriods', 'departments'));
    }

    public function update(Request $request, int $personnelId)
    {
        $validated = $this->validatePersonnel($request, $personnelId);

        $this->libraryPersonnelService->updatePersonnel($personnelId, $validated);

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel updated successfully.');
    }

    public function destroy(int $personnelId)
    {
        $this->libraryPersonnelService->deletePersonnel($personnelId);

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel deleted successfully.');
    }

    private function validatePersonnel(Request $request, ?int $personnelId = null): array
    {
        $passwordRules = $personnelId
            ? ['nullable', 'string', 'min:6', 'max:255']
            : ['required', 'string', 'min:6', 'max:255'];

        return $request->validate([
            'personnel_role' => ['required', 'in:admin,staff,student staff'],
            'username' => [
                'required',
                'string',
                'max:50',
                'unique:library_personnel,username,' . $personnelId . ',personnel_id',
            ],
            'password' => $passwordRules,
            'department_id' => ['required', 'integer', 'exists:department,department_id'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:3'],
            'shift_day' => ['required', 'in:Monday,Tuesday,Wednesday,Thursday,Friday'],
            'shift_period' => ['required', 'in:Morning,Afternoon'],
        ]);
    }
}