<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function getAdminPageData(?string $search = null, ?string $role = null, ?string $date = null): array
    {
        $personnelId = session('personnel_id');

        return [
            'currentPersonnelActiveLog' => $this->getCurrentPersonnelActiveLog($personnelId),
            'activeLogs' => $this->getActiveLogs(),
            'logs' => $this->getLogs($search, $role, $date),
            'roles' => $this->attendanceRoles(),
        ];
    }

    public function timeInCurrentPersonnel(): void
    {
        $personnelId = session('personnel_id');

        if (!$personnelId) {
            throw ValidationException::withMessages([
                'personnel' => 'No logged-in personnel account found.',
            ]);
        }

        DB::transaction(function () use ($personnelId) {
            $personnel = DB::table('library_personnel')
                ->where('personnel_id', $personnelId)
                ->first();

            if (!$personnel) {
                throw ValidationException::withMessages([
                    'personnel' => 'Personnel account does not exist.',
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

    public function timeOutCurrentPersonnel(): void
    {
        $personnelId = session('personnel_id');

        if (!$personnelId) {
            throw ValidationException::withMessages([
                'personnel' => 'No logged-in personnel account found.',
            ]);
        }

        DB::transaction(function () use ($personnelId) {
            $log = DB::table('attendance_log')
                ->where('personnel_id', $personnelId)
                ->whereNull('time_out')
                ->lockForUpdate()
                ->first();

            if (!$log) {
                throw ValidationException::withMessages([
                    'personnel' => 'You do not have an active time-in record.',
                ]);
            }

            DB::table('attendance_log')
                ->where('log_id', $log->log_id)
                ->update([
                    'time_out' => now()->format('H:i:s'),
                ]);
        });
    }

    public function timeInMemberByReference(string $referenceNumber): void
    {
        DB::transaction(function () use ($referenceNumber) {
            $member = DB::table('library_member')
                ->where('reference_number', trim($referenceNumber))
                ->first();

            if (!$member) {
                throw ValidationException::withMessages([
                    'member_reference' => 'No library member found with that reference number.',
                ]);
            }

            $this->preventDuplicateActiveLog('member_id', $member->member_id);

            DB::table('attendance_log')->insert([
                'role' => $member->member_role,
                'personnel_id' => null,
                'member_id' => $member->member_id,
                'visitor_id' => null,
                'log_date' => now()->toDateString(),
                'time_in' => now()->format('H:i:s'),
                'time_out' => null,
            ]);
        });
    }

    public function timeOutMemberByReference(string $referenceNumber): void
    {
        DB::transaction(function () use ($referenceNumber) {
            $member = DB::table('library_member')
                ->where('reference_number', trim($referenceNumber))
                ->first();

            if (!$member) {
                throw ValidationException::withMessages([
                    'member_reference' => 'No library member found with that reference number.',
                ]);
            }

            $log = DB::table('attendance_log')
                ->where('member_id', $member->member_id)
                ->whereNull('time_out')
                ->lockForUpdate()
                ->first();

            if (!$log) {
                throw ValidationException::withMessages([
                    'member_reference' => 'No active time-in record found for this reference number.',
                ]);
            }

            DB::table('attendance_log')
                ->where('log_id', $log->log_id)
                ->update([
                    'time_out' => now()->format('H:i:s'),
                ]);
        });
    }

    public function timeInVisitor(array $data): void
    {
        DB::transaction(function () use ($data) {
            DB::insert(
                'INSERT INTO library_visitor (last_name, first_name, `m.i.`) VALUES (?, ?, ?)',
                [
                    trim($data['last_name']),
                    trim($data['first_name']),
                    $data['middle_initial'] ? trim($data['middle_initial']) : null,
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

    public function timeOutVisitor(array $data): void
    {
        DB::transaction(function () use ($data) {
            $lastName = trim($data['last_name']);
            $firstName = trim($data['first_name']);
            $middleInitial = $data['middle_initial'] ? trim($data['middle_initial']) : null;

            $matches = DB::table('attendance_log')
                ->join('library_visitor', 'attendance_log.visitor_id', '=', 'library_visitor.visitor_id')
                ->whereNull('attendance_log.time_out')
                ->whereRaw('LOWER(TRIM(library_visitor.last_name)) = ?', [strtolower($lastName)])
                ->whereRaw('LOWER(TRIM(library_visitor.first_name)) = ?', [strtolower($firstName)])
                ->where(function ($query) use ($middleInitial) {
                    if ($middleInitial) {
                        $query->whereRaw('LOWER(TRIM(library_visitor.`m.i.`)) = ?', [strtolower($middleInitial)]);
                    } else {
                        $query->whereNull('library_visitor.`m.i.`')
                            ->orWhere('library_visitor.`m.i.`', '');
                    }
                })
                ->select('attendance_log.log_id')
                ->get();

            if ($matches->isEmpty()) {
                throw ValidationException::withMessages([
                    'visitor' => 'No active visitor time-in record found for this name.',
                ]);
            }

            if ($matches->count() > 1) {
                throw ValidationException::withMessages([
                    'visitor' => 'Multiple active visitor records match this name. Please ask library personnel for assistance.',
                ]);
            }

            DB::table('attendance_log')
                ->where('log_id', $matches->first()->log_id)
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
                $column => 'There is already an active time-in record. Please time out first.',
            ]);
        }
    }

    private function getCurrentPersonnelActiveLog(?int $personnelId): ?object
    {
        if (!$personnelId) {
            return null;
        }

        return DB::table('attendance_log')
            ->where('personnel_id', $personnelId)
            ->whereNull('time_out')
            ->first();
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

                'library_member.reference_number as member_reference_number',
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
                    ->orWhere('library_member.reference_number', 'like', "%{$search}%")
                    ->orWhere('library_personnel.first_name', 'like', "%{$search}%")
                    ->orWhere('library_personnel.last_name', 'like', "%{$search}%")
                    ->orWhere('library_member.first_name', 'like', "%{$search}%")
                    ->orWhere('library_member.last_name', 'like', "%{$search}%")
                    ->orWhere('library_visitor.first_name', 'like', "%{$search}%")
                    ->orWhere('library_visitor.last_name', 'like', "%{$search}%");
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