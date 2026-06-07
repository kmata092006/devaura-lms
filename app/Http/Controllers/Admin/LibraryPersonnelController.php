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
                    ->orWhere('personnel.`m.i.`', 'like', "%{$search}%")
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

        $personnel->getCollection()->transform(function ($person) {
            $middleInitial = $person->middle_initial ? ' ' . $person->middle_initial : '';

            $person->full_name = "{$person->last_name}, {$person->first_name}{$middleInitial}";
            $person->department_name = $person->department_name ?? '-';

            return $person;
        });

        $roles = $this->roles;

        $departments = DB::table('department')
            ->orderBy('department_name')
            ->get();

        return view('admin.personnel.index', compact('personnel', 'roles', 'departments'));
    }

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

        DB::table('library_personnel')->insert([
            'personnel_role' => $validated['personnel_role'],
            'username' => $validated['username'],
            'password_hash' => Hash::make($validated['password']),
            'department_id' => $validated['department_id'],
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'm.i.' => $this->formatMiddleInitial($validated['middle_initial'] ?? null),
            'shift_day' => $validated['shift_day'],
            'shift_period' => $validated['shift_period'],
        ]);

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel account created successfully.');
    }

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

        $updateData = [
            'personnel_role' => $validated['personnel_role'],
            'username' => $validated['username'],
            'department_id' => $validated['department_id'],
            'last_name' => $validated['last_name'],
            'first_name' => $validated['first_name'],
            'm.i.' => $this->formatMiddleInitial($validated['middle_initial'] ?? null),
            'shift_day' => $validated['shift_day'],
            'shift_period' => $validated['shift_period'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password_hash'] = Hash::make($validated['password']);
        }

        DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->update($updateData);

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel account updated successfully.');
    }

    public function destroy($personnelId)
    {
        if ((int) $personnelId === (int) session('personnel_id')) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'You cannot delete your own logged-in account.']);
        }

        $personnel = DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->first();

        if (!$personnel) {
            return redirect()
                ->route('admin.personnel.index')
                ->withErrors(['personnel' => 'Library personnel record not found.']);
        }

        DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->delete();

        return redirect()
            ->route('admin.personnel.index')
            ->with('success', 'Library personnel account deleted successfully.');
    }

    private function formatMiddleInitial(?string $middleInitial): ?string
    {
        if (!$middleInitial) {
            return null;
        }

        $middleInitial = strtoupper(trim($middleInitial));

        if ($middleInitial === '') {
            return null;
        }

        return $middleInitial;
    }
}