<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function getDepartments(?string $search = null): LengthAwarePaginator
    {
        $query = DB::table('department')
            ->select(
                'department.department_id',
                'department.department_name',
                DB::raw('COUNT(library_personnel.personnel_id) as personnel_count')
            )
            ->leftJoin('library_personnel', 'department.department_id', '=', 'library_personnel.department_id')
            ->groupBy('department.department_id', 'department.department_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('department.department_id', 'like', "%{$search}%")
                    ->orWhere('department.department_name', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('department.department_name')
            ->paginate(10)
            ->withQueryString();
    }

    public function getDepartmentOptions(): Collection
    {
        return DB::table('department')
            ->select('department_id', 'department_name')
            ->orderBy('department_name')
            ->get();
    }

    public function findDepartment(int $departmentId): object
    {
        $department = DB::table('department')
            ->where('department_id', $departmentId)
            ->first();

        if (!$department) {
            abort(404, 'Department not found.');
        }

        return $department;
    }

    public function createDepartment(array $data): void
    {
        DB::table('department')->insert([
            'department_name' => $data['department_name'],
        ]);
    }

    public function updateDepartment(int $departmentId, array $data): void
    {
        DB::table('department')
            ->where('department_id', $departmentId)
            ->update([
                'department_name' => $data['department_name'],
            ]);
    }

    public function deleteDepartment(int $departmentId): void
    {
        $isUsed = DB::table('library_personnel')
            ->where('department_id', $departmentId)
            ->exists();

        if ($isUsed) {
            throw ValidationException::withMessages([
                'department' => 'This department cannot be deleted because it is assigned to library personnel.',
            ]);
        }

        DB::table('department')
            ->where('department_id', $departmentId)
            ->delete();
    }
}