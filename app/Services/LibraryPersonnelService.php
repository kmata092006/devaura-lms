<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LibraryPersonnelService
{
    public function getPersonnel(?string $search = null, ?string $role = null, ?string $departmentId = null): LengthAwarePaginator
    {
        $nameExpression = $this->fullNameSql('library_personnel');

        $query = DB::table('library_personnel')
            ->leftJoin('department', 'library_personnel.department_id', '=', 'department.department_id')
            ->select(
                'library_personnel.personnel_id',
                'library_personnel.username',
                'library_personnel.personnel_role',
                'library_personnel.department_id',
                'library_personnel.last_name',
                'library_personnel.first_name',
                DB::raw('library_personnel.`m.i.` as middle_initial'),
                'library_personnel.shift_day',
                'library_personnel.shift_period',
                DB::raw("{$nameExpression} as full_name"),
                DB::raw("COALESCE(department.department_name, '-') as department_name")
            );

        if ($search) {
            $query->where(function ($q) use ($search, $nameExpression) {
                $q->where('library_personnel.personnel_id', 'like', "%{$search}%")
                    ->orWhere('library_personnel.username', 'like', "%{$search}%")
                    ->orWhere('library_personnel.personnel_role', 'like', "%{$search}%")
                    ->orWhere('department.department_name', 'like', "%{$search}%")
                    ->orWhereRaw("{$nameExpression} LIKE ?", ["%{$search}%"]);
            });
        }

        if ($role) {
            $query->where('library_personnel.personnel_role', $role);
        }

        if ($departmentId) {
            $query->where('library_personnel.department_id', $departmentId);
        }

        return $query
            ->orderBy('library_personnel.personnel_role')
            ->orderBy('library_personnel.last_name')
            ->paginate(10)
            ->withQueryString();
    }

    public function findPersonnel(int $personnelId): object
    {
        $personnel = DB::table('library_personnel')
            ->select(
                'personnel_id',
                'username',
                'personnel_role',
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
            abort(404, 'Library personnel not found.');
        }

        return $personnel;
    }

    public function createPersonnel(array $data): void
    {
        DB::table('library_personnel')->insert([
            'username' => $data['username'],
            'password_hash' => Hash::make($data['password']),
            'personnel_role' => $data['personnel_role'],
            'department_id' => $data['department_id'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'm.i.' => $data['middle_initial'] ?? null,
            'shift_day' => $data['shift_day'],
            'shift_period' => $data['shift_period'],
        ]);
    }

    public function updatePersonnel(int $personnelId, array $data): void
    {
        $updateData = [
            'username' => $data['username'],
            'personnel_role' => $data['personnel_role'],
            'department_id' => $data['department_id'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'm.i.' => $data['middle_initial'] ?? null,
            'shift_day' => $data['shift_day'],
            'shift_period' => $data['shift_period'],
        ];

        if (!empty($data['password'])) {
            $updateData['password_hash'] = Hash::make($data['password']);
        }

        DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->update($updateData);
    }

    public function deletePersonnel(int $personnelId): void
    {
        if ((int) session('personnel_id') === $personnelId) {
            throw ValidationException::withMessages([
                'personnel' => 'You cannot delete the currently logged-in account.',
            ]);
        }

        $hasAttendanceLogs = DB::table('attendance_log')
            ->where('personnel_id', $personnelId)
            ->exists();

        if ($hasAttendanceLogs) {
            throw ValidationException::withMessages([
                'personnel' => 'This personnel account cannot be deleted because it already has attendance records.',
            ]);
        }

        DB::table('library_personnel')
            ->where('personnel_id', $personnelId)
            ->delete();
    }

    public function roleOptions(): array
    {
        return [
            'admin',
            'staff',
            'student staff',
        ];
    }

    public function shiftDayOptions(): array
    {
        return [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
        ];
    }

    public function shiftPeriodOptions(): array
    {
        return [
            'Morning',
            'Afternoon',
        ];
    }

    private function fullNameSql(string $alias): string
    {
        return "
            TRIM(
                CONCAT(
                    COALESCE({$alias}.first_name, ''),
                    ' ',
                    CASE
                        WHEN {$alias}.`m.i.` IS NOT NULL AND {$alias}.`m.i.` <> ''
                        THEN CONCAT({$alias}.`m.i.`, '. ')
                        ELSE ''
                    END,
                    COALESCE({$alias}.last_name, '')
                )
            )
        ";
    }
}