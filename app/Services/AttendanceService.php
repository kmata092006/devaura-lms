<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function getAttendancePageData(?string $search = null, ?string $role = null, ?string $date = null): array
    {
        return [
            'personnel' => $this->getPersonnel(),
            'members' => $this->getMembers(),
            'activeLogs' => $this->getActiveLogs(),
            'logs' => $this->getLogs($search, $role, $date),
            'roles' => $this->attendanceRoles(),
        ];
    }

    public function timeInPersonnel(int $personnelId): void
    {
        DB::transaction(function () use ($personnelId) {
            $personnel = DB::table('library_personnel')
                ->where('personnel_id', $personnelId)
                ->first();

            if (!$personnel) {
                throw ValidationException::withMessages([
                    'personnel_id' => 'Selected personnel does not exist.',
                ]);
            }

            $this->preventDuplicateActiveLog('personnel_id', $personnelId);

            DB::table('attendance_log')->insert([
                'role' => $personnel->personnel_role,
                'personnel_id' => $personnelId,
                'member_id' => null,
                'visitor_id' => null,
                'log_date' => now()->toDateString(),
                'time_in' => now()->format('H:i:s'),
                'time_out' => null,
            ]);
        });
    }

    public function timeInMember(int $memberId): void
    {
        DB::transaction(function () use ($memberId) {
            $member = DB::table('library_member')
                ->where('member_id', $memberId)
                ->first();

            if (!$member) {
                throw ValidationException::withMessages([
                    'member_id' => 'Selected library member does not exist.',
                ]);
            }

            $this->preventDuplicateActiveLog('member_id', $memberId);

            DB::table('attendance_log')->insert([
                'role' => $member->member_role,
                'personnel_id' => null,
                'member_id' => $memberId,
                'visitor_id' => null,
                'log_date' => now()->toDateString(),
                'time_in' => now()->format('H:i:s'),
                'time_out' => null,
            ]);
        });
    }

    public function timeInVisitor(array $data): void
    {
        DB::transaction(function () use ($data) {
            DB::insert(
                'INSERT INTO library_visitor (last_name, first_name, `m.i.`) VALUES (?, ?, ?)',
                [
                    $data['last_name'],
                    $data['first_name'],
                    $data['middle_initial'] ?? null,
                ]
            );

            $visitorId = (int) DB::getPdo()->lastInsertId();

            DB::table('attendance_log')->insert([
                'role' => 'visitor',
                'personnel_id' => null,
                'member_id' => null,
                'visitor_id' => $visitorId,
                'log_date' => now()->toDateString(),
                'time_in' => now()->format('H:i:s'),
                'time_out' => null,
            ]);
        });
    }

    public function timeOutLog(int $logId): void
    {
        DB::transaction(function () use ($logId) {
            $log = DB::table('attendance_log')
                ->where('log_id', $logId)
                ->whereNull('time_out')
                ->lockForUpdate()
                ->first();

            if (!$log) {
                throw ValidationException::withMessages([
                    'log_id' => 'This attendance log is not active or has already timed out.',
                ]);
            }

            DB::table('attendance_log')
                ->where('log_id', $logId)
                ->update([
                    'time_out' => now()->format('H:i:s'),
                ]);
        });
    }

    private function preventDuplicateActiveLog(string $column, int $id): void
    {
        $activeLog = DB::table('attendance_log')
            ->where($column, $id)
            ->whereNull('time_out')
            ->first();

        if ($activeLog) {
            throw ValidationException::withMessages([
                $column => 'This person already has an active time-in record. Please time out first.',
            ]);
        }
    }

    private function getPersonnel(): Collection
    {
        return DB::table('library_personnel')
            ->select(
                'personnel_id',
                'personnel_role',
                'username',
                'first_name',
                'last_name',
                DB::raw('`m.i.` as middle_initial')
            )
            ->orderBy('personnel_role')
            ->orderBy('last_name')
            ->get();
    }

    private function getMembers(): Collection
    {
        return DB::table('library_member')
            ->select(
                'member_id',
                'member_role',
                'first_name',
                'last_name',
                DB::raw('`m.i.` as middle_initial')
            )
            ->orderBy('member_role')
            ->orderBy('last_name')
            ->get();
    }

    private function getActiveLogs(): Collection
    {
        return DB::table('attendance_log')
            ->leftJoin('library_personnel', 'attendance_log.personnel_id', '=', 'library_personnel.personnel_id')
            ->leftJoin('library_member', 'attendance_log.member_id', '=', 'library_member.member_id')
            ->leftJoin('library_visitor', 'attendance_log.visitor_id', '=', 'library_visitor.visitor_id')
            ->select(
                'attendance_log.log_id',
                'attendance_log.role',
                'attendance_log.log_date',
                'attendance_log.time_in',
                'attendance_log.personnel_id',
                'attendance_log.member_id',
                'attendance_log.visitor_id',

                'library_personnel.first_name as personnel_first_name',
                'library_personnel.last_name as personnel_last_name',
                DB::raw('library_personnel.`m.i.` as personnel_middle_initial'),

                'library_member.first_name as member_first_name',
                'library_member.last_name as member_last_name',
                DB::raw('library_member.`m.i.` as member_middle_initial'),

                'library_visitor.first_name as visitor_first_name',
                'library_visitor.last_name as visitor_last_name',
                DB::raw('library_visitor.`m.i.` as visitor_middle_initial')
            )
            ->whereNull('attendance_log.time_out')
            ->orderByDesc('attendance_log.log_id')
            ->get();
    }

    private function getLogs(?string $search = null, ?string $role = null, ?string $date = null): LengthAwarePaginator
    {
        $query = DB::table('attendance_log')
            ->leftJoin('library_personnel', 'attendance_log.personnel_id', '=', 'library_personnel.personnel_id')
            ->leftJoin('library_member', 'attendance_log.member_id', '=', 'library_member.member_id')
            ->leftJoin('library_visitor', 'attendance_log.visitor_id', '=', 'library_visitor.visitor_id')
            ->select(
                'attendance_log.log_id',
                'attendance_log.role',
                'attendance_log.log_date',
                'attendance_log.time_in',
                'attendance_log.time_out',
                'attendance_log.personnel_id',
                'attendance_log.member_id',
                'attendance_log.visitor_id',

                'library_personnel.first_name as personnel_first_name',
                'library_personnel.last_name as personnel_last_name',
                DB::raw('library_personnel.`m.i.` as personnel_middle_initial'),

                'library_member.first_name as member_first_name',
                'library_member.last_name as member_last_name',
                DB::raw('library_member.`m.i.` as member_middle_initial'),

                'library_visitor.first_name as visitor_first_name',
                'library_visitor.last_name as visitor_last_name',
                DB::raw('library_visitor.`m.i.` as visitor_middle_initial')
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('attendance_log.log_id', 'like', "%{$search}%")
                    ->orWhere('attendance_log.role', 'like', "%{$search}%")
                    ->orWhere('attendance_log.log_date', 'like', "%{$search}%")
                    ->orWhere('attendance_log.time_in', 'like', "%{$search}%")
                    ->orWhere('attendance_log.time_out', 'like', "%{$search}%")
                    ->orWhere('library_personnel.first_name', 'like', "%{$search}%")
                    ->orWhere('library_personnel.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('library_personnel.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('library_member.first_name', 'like', "%{$search}%")
                    ->orWhere('library_member.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('library_member.`m.i.` LIKE ?', ["%{$search}%"])
                    ->orWhere('library_visitor.first_name', 'like', "%{$search}%")
                    ->orWhere('library_visitor.last_name', 'like', "%{$search}%")
                    ->orWhereRaw('library_visitor.`m.i.` LIKE ?', ["%{$search}%"]);
            });
        }

        if ($role) {
            $query->where('attendance_log.role', $role);
        }

        if ($date) {
            $query->whereDate('attendance_log.log_date', $date);
        }

        return $query
            ->orderByDesc('attendance_log.log_date')
            ->orderByDesc('attendance_log.time_in')
            ->paginate(10)
            ->withQueryString();
    }

    private function attendanceRoles(): array
    {
        return [
            'admin',
            'staff',
            'student staff',
            'student',
            'faculty',
            'visitor',
        ];
    }
}