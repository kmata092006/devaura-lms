<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LibraryPersonnelController extends Controller
{
    private array $roles = [
        'admin',
        'staff',
        'student staff',
    ];

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
    ];

    private array $protectedUsernames = [
        'admin',
        'staff',
        'student staff',
    ];

    /**
     * Shows paginated personnel records with search and filters.
     */
    public function index(Request $request)
    {
        $query = DB::table('library_personnel as personnel')
            ->leftJoin('department as department', 'personnel.department_id', '=', 'department.department_id')
            ->select(
                'personnel.personnel_id',
                'personnel.username',
                'personnel.personnel_role',
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
                    ->orWhere('personnel.personnel_role', 'like', "%{$search}%")
                    ->orWhere('personnel.last_name', 'like', "%{$search}%")
                    ->orWhere('personnel.first_name', 'like', "%{$search}%")
                    ->orWhereRaw('personnel.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('department.department_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('personnel.personnel_role', $request->query('role'));
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

        $roles = $this->roles;

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
        $roles = $this->roles;
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
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:3'],

            'personnel_role' => ['required', Rule::in($this->roles)],
            'department_id' => ['required', 'integer', 'exists:department,department_id'],
            'shift_day' => ['required', Rule::in($this->shiftDays)],
            'shift_period' => ['required', Rule::in($this->shiftPeriods)],

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
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'This username is already taken.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        DB::insert(
            'INSERT INTO library_personnel
                (personnel_role, username, password_hash, department_id, last_name, first_name, `m.i.`, shift_day, shift_period)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $validated['personnel_role'],
                $validated['username'],
                Hash::make($validated['password']),
                $validated['department_id'],
                trim($validated['last_name']),
                trim($validated['first_name']),
                $this->formatMiddleInitial($validated['middle_initial'] ?? null),
                $validated['shift_day'],
                $validated['shift_period'],
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
        $personnel = DB::table('library_personnel')
            ->select(
                'personnel_id',
                'personnel_role',
                'username',
                'department_id',
                'last_name',
                'first_name',
                DB::raw('`m.i.` as middle_initial'),
                'shift_day',
                'shift_period'
            )
            ->where('personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'Library personnel record not found.']);
        }

        $roles = $this->roles;
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

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:3'],

            'personnel_role' => ['required', Rule::in($this->roles)],
            'department_id' => ['required', 'integer', 'exists:department,department_id'],
            'shift_day' => ['required', Rule::in($this->shiftDays)],
            'shift_period' => ['required', Rule::in($this->shiftPeriods)],

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
            'username.alpha_dash' => 'The username may only contain letters, numbers, dashes, and underscores.',
            'username.unique' => 'This username is already taken.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        if (!empty($validated['password'])) {
            DB::update(
                'UPDATE library_personnel
                 SET personnel_role = ?,
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
                    $validated['personnel_role'],
                    $validated['username'],
                    $validated['department_id'],
                    trim($validated['last_name']),
                    trim($validated['first_name']),
                    $this->formatMiddleInitial($validated['middle_initial'] ?? null),
                    $validated['shift_day'],
                    $validated['shift_period'],
                    Hash::make($validated['password']),
                    $personnelId,
                ]
            );
        } else {
            DB::update(
                'UPDATE library_personnel
                 SET personnel_role = ?,
                     username = ?,
                     department_id = ?,
                     last_name = ?,
                     first_name = ?,
                     `m.i.` = ?,
                     shift_day = ?,
                     shift_period = ?
                 WHERE personnel_id = ?',
                [
                    $validated['personnel_role'],
                    $validated['username'],
                    $validated['department_id'],
                    trim($validated['last_name']),
                    trim($validated['first_name']),
                    $this->formatMiddleInitial($validated['middle_initial'] ?? null),
                    $validated['shift_day'],
                    $validated['shift_period'],
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
