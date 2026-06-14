<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    /**
     * Student Staff stores a half-day shift; Staff displays as Whole Day.
     */
    private array $shiftPeriods = [
        'Morning',
        'Afternoon',
    ];

    /**
     * Normalized usernames of protected default accounts.
     */
    private array $protectedUsernames = [
        'admin',
        'staff',
        'student_staff',
    ];

    /**
     * Receives the service used to load available personnel roles.
     */
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Shows filtered personnel records.
     */
    public function index(Request $request)
    {
        $hasSystemAccountColumn = Schema::hasColumn(
            'library_personnel',
            'is_system_account'
        );

        $query = DB::table('library_personnel as personnel')
            ->join(
                'personnel_role as role',
                'personnel.personnel_role_id',
                '=',
                'role.personnel_role_id'
            )
            ->leftJoin(
                'department as department',
                'personnel.department_id',
                '=',
                'department.department_id'
            )
            ->select(
                'personnel.personnel_id',
                'personnel.username',
                'role.personnel_role_name as personnel_role',
                'personnel.department_id',
                'department.department_name',
                'personnel.last_name',
                'personnel.first_name',
                DB::raw('personnel.`m.i.` as middle_initial'),
                'personnel.shift_day',
                'personnel.shift_period'
            );

        if ($hasSystemAccountColumn) {
            $query->addSelect('personnel.is_system_account');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));

            $query->where(function ($subquery) use ($search) {
                $subquery
                    ->where(
                        'personnel.username',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'role.personnel_role_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'personnel.last_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'personnel.first_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereRaw(
                        'personnel.`m.i.` LIKE ?',
                        ["%{$search}%"]
                    )
                    ->orWhere(
                        'department.department_name',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if ($request->filled('role')) {
            $query->where(
                'role.personnel_role_name',
                $request->query('role')
            );
        }

        if ($request->filled('department_id')) {
            $query->where(
                'personnel.department_id',
                $request->query('department_id')
            );
        }

        $personnel = $query
            ->orderBy('personnel.personnel_id')
            ->paginate(10)
            ->withQueryString();

        $personnel->getCollection()->transform(
            function ($person) use ($hasSystemAccountColumn) {
                $middleInitial = $person->middle_initial
                    ? ' ' . rtrim(
                        (string) $person->middle_initial,
                        '.'
                    ) . '.'
                    : '';

                $person->full_name =
                    "{$person->last_name}, " .
                    "{$person->first_name}{$middleInitial}";

                $person->department_name =
                    $person->department_name ?? null;

                $normalizedRole = $this->normalizeRoleName(
                    $person->personnel_role
                );

                // Display Staff as Whole Day while storing a null shift period.
                if ($normalizedRole === 'staff') {
                    $person->shift_period = 'Whole Day';
                }

                $systemAccountFlag = $hasSystemAccountColumn
                    ? (bool) ($person->is_system_account ?? false)
                    : false;

                $person->is_protected_account =
                    $systemAccountFlag ||
                    $this->isProtectedUsername(
                        (string) $person->username
                    );

                return $person;
            }
        );

        $roles = $this->roleService
            ->getRoles()
            ->pluck('personnel_role_name')
            ->toArray();

        $departments = DB::table('department')
            ->orderBy('department_name')
            ->get();

        return view(
            'admin.personnel.index',
            compact('personnel', 'roles', 'departments')
        );
    }

    /**
     * Shows the create-personnel form.
     */
    public function create()
    {
        $roles = $this->roleService
            ->getRoles()
            ->pluck('personnel_role_name')
            ->toArray();

        $departments = DB::table('department')
            ->orderBy('department_name')
            ->get();

        $shiftDays = $this->shiftDays;
        $shiftPeriods = $this->shiftPeriods;

        return view(
            'admin.personnel.create',
            compact(
                'roles',
                'departments',
                'shiftDays',
                'shiftPeriods'
            )
        );
    }

    /**
     * Creates a personnel account.
     */
    public function store(Request $request)
    {
        $this->normalizeRequestText($request);

        $dbRoles = $this->roleService->getRoles();

        $allowedRoleNames = $dbRoles
            ->pluck('personnel_role_name')
            ->toArray();

        $normalizedRole = $this->normalizeRoleName(
            $request->input('personnel_role')
        );

        $validated = $request->validate(
            $this->validationRules(
                $normalizedRole,
                $allowedRoleNames
            ),
            $this->validationMessages()
        );

        $roleRecord = $dbRoles->first(
            fn ($role) =>
                $this->normalizeRoleName(
                    $role->personnel_role_name
                ) === $normalizedRole
        );

        if (!$roleRecord) {
            return back()
                ->withInput()
                ->withErrors([
                    'personnel_role' =>
                        'The selected personnel role could not be found.',
                ]);
        }

        [$departmentId, $shiftDay, $shiftPeriod] =
            $this->resolveAssignmentValues(
                $normalizedRole,
                $validated
            );

        DB::insert(
            'INSERT INTO library_personnel
                (
                    personnel_role_id,
                    username,
                    password_hash,
                    department_id,
                    last_name,
                    first_name,
                    `m.i.`,
                    shift_day,
                    shift_period
                )
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $roleRecord->personnel_role_id,
                $validated['username'],
                Hash::make($validated['password']),
                $departmentId,
                $this->formatPersonName(
                    $validated['last_name']
                ),
                $this->formatPersonName(
                    $validated['first_name']
                ),
                $this->formatMiddleInitial(
                    $validated['middle_initial'] ?? null
                ),
                $shiftDay,
                $shiftPeriod,
            ]
        );

        return redirect()
            ->route('admin.personnel.index')
            ->with(
                'success',
                'Library personnel account created successfully.'
            );
    }

    /**
     * Shows the edit-personnel form.
     */
    public function edit($personnelId)
    {
        $personnel = DB::table('library_personnel as personnel')
            ->join(
                'personnel_role as role',
                'personnel.personnel_role_id',
                '=',
                'role.personnel_role_id'
            )
            ->select(
                'personnel.personnel_id',
                'role.personnel_role_name as personnel_role',
                'personnel.username',
                'personnel.department_id',
                'personnel.last_name',
                'personnel.first_name',
                DB::raw('personnel.`m.i.` as middle_initial'),
                'personnel.shift_day',
                'personnel.shift_period'
            )
            ->where('personnel.personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors([
                    'personnel' =>
                        'Library personnel record not found.',
                ]);
        }

        $roles = $this->roleService
            ->getRoles()
            ->pluck('personnel_role_name')
            ->toArray();

        $departments = DB::table('department')
            ->orderBy('department_name')
            ->get();

        $shiftDays = $this->shiftDays;
        $shiftPeriods = $this->shiftPeriods;

        return view(
            'admin.personnel.edit',
            compact(
                'personnel',
                'roles',
                'departments',
                'shiftDays',
                'shiftPeriods'
            )
        );
    }

    /**
     * Updates a personnel account.
     */
    public function update(Request $request, $personnelId)
    {
        $personnel = DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors([
                    'personnel' =>
                        'Library personnel record not found.',
                ]);
        }

        $this->normalizeRequestText($request);

        $dbRoles = $this->roleService->getRoles();

        $allowedRoleNames = $dbRoles
            ->pluck('personnel_role_name')
            ->toArray();

        $normalizedRole = $this->normalizeRoleName(
            $request->input('personnel_role')
        );

        $validated = $request->validate(
            $this->validationRules(
                $normalizedRole,
                $allowedRoleNames,
                (int) $personnelId
            ),
            $this->validationMessages()
        );

        $roleRecord = $dbRoles->first(
            fn ($role) =>
                $this->normalizeRoleName(
                    $role->personnel_role_name
                ) === $normalizedRole
        );

        if (!$roleRecord) {
            return back()
                ->withInput()
                ->withErrors([
                    'personnel_role' =>
                        'The selected personnel role could not be found.',
                ]);
        }

        [$departmentId, $shiftDay, $shiftPeriod] =
            $this->resolveAssignmentValues(
                $normalizedRole,
                $validated
            );

        $parameters = [
            $roleRecord->personnel_role_id,
            $validated['username'],
            $departmentId,
            $this->formatPersonName(
                $validated['last_name']
            ),
            $this->formatPersonName(
                $validated['first_name']
            ),
            $this->formatMiddleInitial(
                $validated['middle_initial'] ?? null
            ),
            $shiftDay,
            $shiftPeriod,
        ];

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
                    ...$parameters,
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
                    ...$parameters,
                    $personnelId,
                ]
            );
        }

        return redirect()
            ->route('admin.personnel.index')
            ->with(
                'success',
                'Library personnel account updated successfully.'
            );
    }

    /**
     * Deletes an ordinary personnel account.
     */
    public function destroy($personnelId)
    {
        $personnel = DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors([
                    'personnel' =>
                        'Library personnel record not found.',
                ]);
        }

        if ((int) $personnelId === (int) session('personnel_id')) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors([
                    'personnel' =>
                        'You cannot delete your currently logged-in account.',
                ]);
        }

        $hasSystemAccountColumn = Schema::hasColumn(
            'library_personnel',
            'is_system_account'
        );

        $systemAccountFlag = $hasSystemAccountColumn
            ? (bool) ($personnel->is_system_account ?? false)
            : false;

        if (
            $systemAccountFlag ||
            $this->isProtectedUsername(
                (string) $personnel->username
            )
        ) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors([
                    'personnel' =>
                        'Default Admin, Staff, and Student Staff accounts cannot be deleted.',
                ]);
        }

        $hasAttendanceRecords = DB::table('attendance_log')
            ->where('personnel_id', $personnelId)
            ->exists();

        $hasAuditRecords = DB::table('audit_trail')
            ->where('personnel_id', $personnelId)
            ->exists();

        if ($hasAttendanceRecords || $hasAuditRecords) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors([
                    'personnel' =>
                        'This account cannot be deleted because it already has attendance or audit records.',
                ]);
        }

        DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->delete();

        return redirect()
            ->route('admin.personnel.index')
            ->with(
                'success',
                'Library personnel account deleted successfully.'
            );
    }

    /**
     * Builds role-aware personnel validation rules.
     */
    private function validationRules(
        string $normalizedRole,
        array $allowedRoleNames,
        ?int $personnelId = null
    ): array {
        $usernameUniqueRule = Rule::unique(
            'library_personnel',
            'username'
        );

        if ($personnelId !== null) {
            $usernameUniqueRule->ignore(
                $personnelId,
                'personnel_id'
            );
        }

        return [
            'last_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^(?=.*\p{L})[\p{L}\s.\'’\-]+$/u',
            ],

            'first_name' => [
                'required',
                'string',
                'max:50',
                'regex:/^(?=.*\p{L})[\p{L}\s.\'’\-]+$/u',
            ],

            'middle_initial' => [
                'nullable',
                'string',
                'max:3',
                'regex:/^(?:\p{L}{1,3}|\p{L}{1,2}\.)$/u',
            ],

            'personnel_role' => [
                'required',
                Rule::in($allowedRoleNames),
            ],

            'department_id' => $normalizedRole === 'student staff'
                ? ['nullable']
                : [
                    'required',
                    'integer',
                    'exists:department,department_id',
                ],

            'shift_day' => $normalizedRole === 'admin'
                ? ['nullable']
                : [
                    'required',
                    Rule::in($this->shiftDays),
                ],

            'shift_period' => $normalizedRole === 'student staff'
                ? [
                    'required',
                    Rule::in($this->shiftPeriods),
                ]
                : ['nullable'],

            'username' => [
                'required',
                'string',
                'min:4',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                $usernameUniqueRule,
            ],

            'password' => $personnelId === null
                ? [
                    'required',
                    'string',
                    'min:8',
                    'confirmed',
                ]
                : [
                    'nullable',
                    'string',
                    'min:8',
                    'confirmed',
                ],
        ];
    }

    /**
     * Provides personnel validation messages.
     */
    private function validationMessages(): array
    {
        return [
            'last_name.required' =>
                'The last name is required.',

            'last_name.regex' =>
                'The last name may only contain letters, spaces, apostrophes, periods, and hyphens. Numbers are not allowed.',

            'first_name.required' =>
                'The first name is required.',

            'first_name.regex' =>
                'The first name may only contain letters, spaces, apostrophes, periods, and hyphens. Numbers are not allowed.',

            'middle_initial.regex' =>
                'The middle initial may only contain one to three letters and an optional period.',

            'personnel_role.required' =>
                'Please select a personnel role.',

            'personnel_role.in' =>
                'The selected personnel role is invalid.',

            'department_id.required' =>
                'A department is required for Admin and Staff accounts.',

            'department_id.exists' =>
                'The selected department does not exist.',

            'shift_day.required' =>
                'Please select a shift day.',

            'shift_day.in' =>
                'The selected shift day is invalid.',

            'shift_period.required' =>
                'Student Staff must select Morning or Afternoon.',

            'shift_period.in' =>
                'Student Staff may only select Morning or Afternoon.',

            'username.required' =>
                'The username is required.',

            'username.min' =>
                'The username must be at least 4 characters.',

            'username.max' =>
                'The username must not exceed 50 characters.',

            'username.regex' =>
                'The username may only contain letters, numbers, dashes, and underscores.',

            'username.unique' =>
                'This username is already taken.',

            'password.required' =>
                'The password is required.',

            'password.min' =>
                'The password must be at least 8 characters.',

            'password.confirmed' =>
                'The password confirmation does not match.',
        ];
    }

    /**
     * Applies role-specific department and shift rules.
     */
    private function resolveAssignmentValues(
        string $normalizedRole,
        array $validated
    ): array {
        $departmentId = $normalizedRole === 'student staff'
            ? null
            : (int) $validated['department_id'];

        $shiftDay = $normalizedRole === 'admin'
            ? null
            : $validated['shift_day'];

        // Staff displays as Whole Day while storing a null shift period.
        $shiftPeriod = $normalizedRole === 'student staff'
            ? $validated['shift_period']
            : null;

        return [$departmentId, $shiftDay, $shiftPeriod];
    }

    /**
     * Normalizes submitted text before validation.
     */
    private function normalizeRequestText(Request $request): void
    {
        $middleInitial = trim(
            (string) $request->input('middle_initial', '')
        );

        $request->merge([
            'last_name' => trim(
                (string) $request->input('last_name', '')
            ),

            'first_name' => trim(
                (string) $request->input('first_name', '')
            ),

            'middle_initial' => $middleInitial === ''
                ? null
                : $middleInitial,

            'username' => trim(
                (string) $request->input('username', '')
            ),
        ]);
    }

    /**
     * Converts role names into the lowercase comparison format.
     */
    private function normalizeRoleName(?string $roleName): string
    {
        return strtolower(trim((string) $roleName));
    }

    /**
     * Normalizes old and new Student Staff username formats.
     */
    private function normalizeUsernameKey(string $username): string
    {
        $username = strtolower(trim($username));

        return preg_replace(
            '/[\s-]+/',
            '_',
            $username
        ) ?? $username;
    }

    /**
     * Checks whether a username belongs to a default system account.
     */
    private function isProtectedUsername(string $username): bool
    {
        return in_array(
            $this->normalizeUsernameKey($username),
            $this->protectedUsernames,
            true
        );
    }

    /**
     * Trims a name and collapses repeated spaces.
     */
    private function formatPersonName(string $name): string
    {
        return preg_replace(
            '/\s+/u',
            ' ',
            trim($name)
        ) ?? trim($name);
    }

    /**
     * Stores middle initials in uppercase without a trailing period.
     */
    private function formatMiddleInitial(
        ?string $middleInitial
    ): ?string {
        if ($middleInitial === null) {
            return null;
        }

        $middleInitial = strtoupper(trim($middleInitial));
        $middleInitial = rtrim($middleInitial, '.');

        return $middleInitial === ''
            ? null
            : $middleInitial;
    }
}
