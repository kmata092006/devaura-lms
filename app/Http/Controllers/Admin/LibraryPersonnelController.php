<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LibraryPersonnelController extends Controller
{
    private RoleService $roleService;

    private array $shiftDays = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
    ];

    private array $shiftPeriods = [
        'Morning',
        'Afternoon',
        'Whole Day', // Added for Staff
    ];

    private array $protectedUsernames = [
        'admin',
        'staff',
        'student staff',
    ];

    /**
     * Injects the RoleService so we can dynamically fetch roles from the DB.
     */
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Shows paginated personnel records with search and filters.
     */
    public function index(Request $request)
    {
        // We now JOIN the personnel_role table to get the string name for the view
        $query = DB::table('library_personnel as personnel')
            ->join('personnel_role as role', 'personnel.personnel_role_id', '=', 'role.personnel_role_id')
            ->leftJoin('department as department', 'personnel.department_id', '=', 'department.department_id')
            ->select(
                'personnel.personnel_id',
                'personnel.username',
                'role.personnel_role_name as personnel_role', // Aliased for backward compatibility with the view
                'personnel.department_id',
                'department.department_name',
                'personnel.last_name',
                'personnel.first_name',
                DB::raw('personnel.`m.i.` as middle_initial'),
                'personnel.shift_day',
                'personnel.shift_period'
            );

        if ($request->filled('search')) {
            $search = trim($request->query('search'));

            $query->where(function ($q) use ($search) {
                $q->where('personnel.personnel_id', 'like', "%{$search}%")
                    ->orWhere('personnel.username', 'like', "%{$search}%")
                    ->orWhere('role.personnel_role_name', 'like', "%{$search}%")
                    ->orWhere('personnel.last_name', 'like', "%{$search}%")
                    ->orWhere('personnel.first_name', 'like', "%{$search}%")
                    ->orWhereRaw('personnel.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('department.department_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role.personnel_role_name', $request->query('role'));
        }

        if ($request->filled('department_id')) {
            $query->where('personnel.department_id', $request->query('department_id'));
        }

        $personnel = $query
            ->orderBy('personnel.personnel_id')
            ->paginate(10)
            ->withQueryString();

        $protectedUsernames = $this->protectedUsernames;

        $personnel->getCollection()->transform(function ($person) use ($protectedUsernames) {
            $middleInitial = $person->middle_initial ? ' ' . $person->middle_initial . '.' : '';

            $person->full_name = "{$person->last_name}, {$person->first_name}{$middleInitial}";
            $person->department_name = $person->department_name ?? '-';
            $person->shift_day = $person->shift_day ?? '-';
            $person->shift_period = $person->shift_period ?? '-';
            $person->is_protected_account = in_array($person->username, $protectedUsernames, true);

            return $person;
        });

        // Dynamically fetch role names for the filter dropdown
        $roles = $this->roleService->getRoles()->pluck('personnel_role_name')->toArray();

        $departments = DB::table('department')
            ->orderBy('department_name')
            ->get();

        return view('admin.personnel.index', compact('personnel', 'roles', 'departments'));
    }

    /**
     * Shows the form for creating a personnel account.
     */
    public function create()
    {
        $roles = $this->roleService->getRoles()->pluck('personnel_role_name')->toArray();
        $shiftDays = $this->shiftDays;
        $shiftPeriods = $this->shiftPeriods;

        $departments = DB::table('department')
            ->orderBy('department_name')
            ->get();

        return view('admin.personnel.create', compact(
            'roles',
            'departments',
            'shiftDays',
            'shiftPeriods'
        ));
    }

    /**
     * Validates and stores a new personnel account.
     */
    public function store(Request $request)
    {
        $dbRoles = $this->roleService->getRoles();
        $allowedRoleNames = $dbRoles->pluck('personnel_role_name')->toArray();

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:3', 'regex:/^[a-zA-Z\.]+$/'],

            'personnel_role' => ['required', Rule::in($allowedRoleNames)],
            'department_id' => [
                Rule::requiredIf(fn () => $request->input('personnel_role') !== 'student staff'), 
                'nullable', 
                'integer', 
                'exists:department,department_id'
            ],
            
            'shift_day' => [
                Rule::requiredIf(fn () => $request->input('personnel_role') !== 'admin'),
                'nullable',
                Rule::in($this->shiftDays)
            ],
            'shift_period' => [
                Rule::requiredIf(fn () => $request->input('personnel_role') !== 'admin'),
                'nullable',
                Rule::in($this->shiftPeriods)
            ],

            'username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'alpha_dash',
                'unique:library_personnel,username',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'last_name.regex' => 'The last name may only contain letters and spaces.',
            'first_name.regex' => 'The first name may only contain letters and spaces.',
            'middle_initial.regex' => 'The middle initial may only contain letters and periods.',
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'This username is already taken.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $departmentId = ($validated['personnel_role'] === 'student staff') ? null : $validated['department_id'];

        if ($validated['personnel_role'] === 'student staff' && ($validated['shift_period'] ?? '') === 'Whole Day') {
            return back()->withErrors(['shift_period' => 'Student staff cannot be assigned to Whole Day shifts.'])->withInput();
        }

        $shiftDay = $validated['personnel_role'] === 'admin' ? null : $validated['shift_day'];
        $shiftPeriod = $validated['personnel_role'] === 'admin' ? null : $validated['shift_period'];

        // Map the string role name to the integer ID for the database
        $roleId = $dbRoles->firstWhere('personnel_role_name', $validated['personnel_role'])->personnel_role_id;

        DB::insert(
            'INSERT INTO library_personnel
                (personnel_role_id, username, password_hash, department_id, last_name, first_name, `m.i.`, shift_day, shift_period)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $roleId,
                $validated['username'],
                Hash::make($validated['password']),
                $departmentId,
                trim($validated['last_name']),
                trim($validated['first_name']),
                $this->formatMiddleInitial($validated['middle_initial'] ?? null),
                $shiftDay,
                $shiftPeriod,
            ]
        );

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel account created successfully.');
    }

    /**
     * Shows the edit form for one personnel account.
     */
    public function edit($personnelId)
    {
        $personnel = DB::table('library_personnel as p')
            ->join('personnel_role as r', 'p.personnel_role_id', '=', 'r.personnel_role_id')
            ->select(
                'p.personnel_id',
                'r.personnel_role_name as personnel_role', // Aligns with the view's expectation
                'p.username',
                'p.department_id',
                'p.last_name',
                'p.first_name',
                DB::raw('p.`m.i.` as middle_initial'),
                'p.shift_day',
                'p.shift_period'
            )
            ->where('p.personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'Library personnel record not found.']);
        }

        $roles = $this->roleService->getRoles()->pluck('personnel_role_name')->toArray();
        $shiftDays = $this->shiftDays;
        $shiftPeriods = $this->shiftPeriods;

        $departments = DB::table('department')
            ->orderBy('department_name')
            ->get();

        return view('admin.personnel.edit', compact(
            'personnel',
            'roles',
            'departments',
            'shiftDays',
            'shiftPeriods'
        ));
    }

    /**
     * Validates and updates an existing personnel account.
     */
    public function update(Request $request, $personnelId)
    {
        $personnel = DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'Library personnel record not found.']);
        }

        $dbRoles = $this->roleService->getRoles();
        $allowedRoleNames = $dbRoles->pluck('personnel_role_name')->toArray();

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:3', 'regex:/^[a-zA-Z\.]+$/'],

            'personnel_role' => ['required', Rule::in($allowedRoleNames)],
            'department_id' => [
                Rule::requiredIf(fn () => $request->input('personnel_role') !== 'student staff'), 
                'nullable', 
                'integer', 
                'exists:department,department_id'
            ],
            
            'shift_day' => [
                Rule::requiredIf(fn () => $request->input('personnel_role') !== 'admin'),
                'nullable',
                Rule::in($this->shiftDays)
            ],
            'shift_period' => [
                Rule::requiredIf(fn () => $request->input('personnel_role') !== 'admin'),
                'nullable',
                Rule::in($this->shiftPeriods)
            ],

            'username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'alpha_dash',
                Rule::unique('library_personnel', 'username')->ignore($personnelId, 'personnel_id'),
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'last_name.regex' => 'The last name may only contain letters and spaces.',
            'first_name.regex' => 'The first name may only contain letters and spaces.',
            'middle_initial.regex' => 'The middle initial may only contain letters and periods.',
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'This username is already taken.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        $departmentId = ($validated['personnel_role'] === 'student staff') ? null : $validated['department_id'];

        if ($validated['personnel_role'] === 'student staff' && ($validated['shift_period'] ?? '') === 'Whole Day') {
            return back()->withErrors(['shift_period' => 'Student staff cannot be assigned to Whole Day shifts.'])->withInput();
        }

        $shiftDay = $validated['personnel_role'] === 'admin' ? null : $validated['shift_day'];
        $shiftPeriod = $validated['personnel_role'] === 'admin' ? null : $validated['shift_period'];

        // Map the string role name to the integer ID for the database update
        $roleId = $dbRoles->firstWhere('personnel_role_name', $validated['personnel_role'])->personnel_role_id;

        if (!empty($validated['password'])) {
            DB::update(
                'UPDATE library_personnel
                 SET personnel_role_id = ?,
                     username = ?,
                     department_id = ?,
                     last_name = ?,
                     first_name = ?,
                     `m.i.` = ?,
                     shift_day = ?,
                     shift_period = ?,
                     password_hash = ?
                 WHERE personnel_id = ?',
                [
                    $roleId,
                    $validated['username'],
                    $departmentId,
                    trim($validated['last_name']),
                    trim($validated['first_name']),
                    $this->formatMiddleInitial($validated['middle_initial'] ?? null),
                    $shiftDay,
                    $shiftPeriod,
                    Hash::make($validated['password']),
                    $personnelId,
                ]
            );
        } else {
            DB::update(
                'UPDATE library_personnel
                 SET personnel_role_id = ?,
                     username = ?,
                     department_id = ?,
                     last_name = ?,
                     first_name = ?,
                     `m.i.` = ?,
                     shift_day = ?,
                     shift_period = ?
                 WHERE personnel_id = ?',
                [
                    $roleId,
                    $validated['username'],
                    $departmentId,
                    trim($validated['last_name']),
                    trim($validated['first_name']),
                    $this->formatMiddleInitial($validated['middle_initial'] ?? null),
                    $shiftDay,
                    $shiftPeriod,
                    $personnelId,
                ]
            );
        }

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel account updated successfully.');
    }

    /**
     * Deletes a personnel account unless it is protected or currently logged in.
     */
    public function destroy($personnelId)
    {
        $personnel = DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'Library personnel record not found.']);
        }

        if ((int) $personnelId === (int) session('personnel_id')) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'You cannot delete your own logged-in account.']);
        }

        if (in_array($personnel->username, $this->protectedUsernames, true)) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'Default system accounts cannot be deleted.']);
        }

        DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->delete();

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel account deleted successfully.');
    }

    /**
     * Normalizes middle initials before saving personnel names.
     */
    private function formatMiddleInitial(?string $middleInitial): ?string
    {
        if (!$middleInitial) {
            return null;
        }

        $middleInitial = strtoupper(trim($middleInitial));
        $middleInitial = rtrim($middleInitial, '.');

        if ($middleInitial === '') {
            return null;
        }

        return $middleInitial;
    }
}