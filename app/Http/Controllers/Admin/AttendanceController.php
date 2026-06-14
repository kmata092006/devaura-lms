<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Shows attendance logs, active status, and admin filters.
     */
    public function index(Request $request)
    {
        $currentPersonnelId = session('personnel_id');

        $validatedFilters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date', 'after_or_equal:today'],
            'date_to' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'date_from.after_or_equal' => 'The start date cannot be earlier than today.',
            'date_to.after_or_equal' => 'The end date cannot be earlier than today.',
        ]);

        $dateFrom = $validatedFilters['date_from'] ?? null;
        $dateTo = $validatedFilters['date_to'] ?? null;

        if ($dateFrom && $dateTo && $dateTo < $dateFrom) {
            return back()
                ->withInput()
                ->withErrors([
                    'date_to' => 'The end date cannot be earlier than the start date.',
                ]);
        }

        $activePersonnelLog = null;

        // Checks if the logged-in personnel currently has an active time-in record.
        if ($currentPersonnelId) {
            $activePersonnelLog = DB::table('attendance_log')
                ->where('personnel_id', $currentPersonnelId)
                ->whereNull('time_out')
                ->orderByDesc('log_id')
                ->first();
        }

        // Join personnel roles so displayed role names stay current.
        $attendanceQuery = DB::table('attendance_log as log')
            ->leftJoin('library_personnel as personnel', 'log.personnel_id', '=', 'personnel.personnel_id')
            ->leftJoin('personnel_role as role', 'personnel.personnel_role_id', '=', 'role.personnel_role_id')
            ->leftJoin('library_member as member', 'log.member_id', '=', 'member.member_id')
            ->leftJoin('library_visitor as visitor', 'log.visitor_id', '=', 'visitor.visitor_id')
            ->select(
                'log.log_id',
                'log.role',
                'log.log_date',
                'log.time_in',
                'log.time_out',
                'log.personnel_id',
                'log.member_id',
                'log.visitor_id',

                'personnel.username as personnel_username',
                'role.personnel_role_name as personnel_role',
                'personnel.last_name as personnel_last_name',
                'personnel.first_name as personnel_first_name',
                DB::raw('personnel.`m.i.` as personnel_middle_initial'),

                'member.reference_number as member_reference_number',
                'member.member_role as member_role',
                'member.last_name as member_last_name',
                'member.first_name as member_first_name',
                DB::raw('member.`m.i.` as member_middle_initial'),

                'visitor.last_name as visitor_last_name',
                'visitor.first_name as visitor_first_name',
                DB::raw('visitor.`m.i.` as visitor_middle_initial')
            );

        // Applies keyword search across attendance, personnel, member, and visitor fields.
        if ($request->filled('search')) {
            $search = trim($request->query('search'));

            $attendanceQuery->where(function ($query) use ($search) {
                $query->where('log.log_id', 'like', "%{$search}%")
                    ->orWhere('log.role', 'like', "%{$search}%")
                    ->orWhere('personnel.username', 'like', "%{$search}%")
                    ->orWhere('personnel.last_name', 'like', "%{$search}%")
                    ->orWhere('personnel.first_name', 'like', "%{$search}%")
                    ->orWhere('member.reference_number', 'like', "%{$search}%")
                    ->orWhere('member.last_name', 'like', "%{$search}%")
                    ->orWhere('member.first_name', 'like', "%{$search}%")
                    ->orWhere('visitor.last_name', 'like', "%{$search}%")
                    ->orWhere('visitor.first_name', 'like', "%{$search}%");
            });
        }

        // Applies optional role and date range filters.
        if ($request->filled('role')) {
            $attendanceQuery->where('log.role', $request->query('role'));
        }

        if ($dateFrom) {
            $attendanceQuery->whereDate('log.log_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $attendanceQuery->whereDate('log.log_date', '<=', $dateTo);
        }

        $attendanceLogs = $attendanceQuery
            ->orderByDesc('log.log_date')
            ->orderByDesc('log.time_in')
            ->paginate(10)
            ->withQueryString();

        // Formats each attendance record into a readable type, name, identifier, and status.
        $attendanceLogs->getCollection()->transform(function ($log) {
            if ($log->personnel_id) {
                $middleInitial = $log->personnel_middle_initial ? ' ' . $log->personnel_middle_initial . '.' : '';

                $log->record_type = 'Personnel';
                $log->display_name = "{$log->personnel_last_name}, {$log->personnel_first_name}{$middleInitial}";
                $log->identifier = $log->personnel_username ?? '-';
            } elseif ($log->member_id) {
                $middleInitial = $log->member_middle_initial ? ' ' . $log->member_middle_initial . '.' : '';

                $log->record_type = 'Member';
                $log->display_name = "{$log->member_last_name}, {$log->member_first_name}{$middleInitial}";
                $log->identifier = $log->member_reference_number ?? '-';
            } elseif ($log->visitor_id) {
                $middleInitial = $log->visitor_middle_initial ? ' ' . $log->visitor_middle_initial . '.' : '';

                $log->record_type = 'Visitor';
                $log->display_name = "{$log->visitor_last_name}, {$log->visitor_first_name}{$middleInitial}";
                $log->identifier = 'Walk-in Visitor';
            } else {
                $log->record_type = 'Unknown';
                $log->display_name = '-';
                $log->identifier = '-';
            }

            $log->status = $log->time_out ? 'Completed' : 'Currently Inside';

            return $log;
        });

        $currentlyInside = DB::table('attendance_log')
            ->whereNull('time_out')
            ->count();

        $totalAttendanceRecords = DB::table('attendance_log')->count();

        $currentAttendanceDate = now()->format('M d, Y');

        // Keeps selected filter values available after search, filter, and pagination.
        $filters = [
            'search' => $validatedFilters['search'] ?? '',
            'role' => $validatedFilters['role'] ?? '',
            'date_from' => $dateFrom ?? '',
            'date_to' => $dateTo ?? '',
        ];

        return view('admin.attendance.index', compact(
            'activePersonnelLog',
            'attendanceLogs',
            'currentlyInside',
            'totalAttendanceRecords',
            'currentAttendanceDate',
            'filters'
        ));
    }

    /**
     * Shows the public attendance kiosk.
     */
    public function kiosk()
    {
        return view('attendance.kiosk');
    }

    /**
     * Records time-in for the currently logged-in personnel account.
     */
    public function timeInPersonnel(Request $request)
    {
        $personnelId = session('personnel_id');
        $personnelRole = session('personnel_role');

        if (!$personnelId) {
            return redirect()
                ->route('login')
                ->withErrors(['attendance' => 'Please log in first.']);
        }

        // Prevents duplicate active time-in records for the same personnel.
        $activeLog = DB::table('attendance_log')
            ->where('personnel_id', $personnelId)
            ->whereNull('time_out')
            ->orderByDesc('log_id')
            ->first();

        if ($activeLog) {
            return redirect()
                ->route('admin.attendance.index')
                ->withErrors(['attendance' => 'You already have an active time-in record. Please time out first.']);
        }

        DB::table('attendance_log')->insert([
            'role' => $personnelRole ?? 'admin',
            'personnel_id' => $personnelId,
            'member_id' => null,
            'visitor_id' => null,
            'log_date' => now()->toDateString(),
            'time_in' => now()->format('H:i:s'),
            'time_out' => null,
        ]);

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Personnel time-in recorded successfully.');
    }

    /**
     * Records time-out for the currently logged-in personnel account.
     */
    public function timeOutPersonnel(Request $request)
    {
        $personnelId = session('personnel_id');

        if (!$personnelId) {
            return redirect()
                ->route('login')
                ->withErrors(['attendance' => 'Please log in first.']);
        }

        // Finds the latest active personnel log before saving the time-out value.
        $activeLog = DB::table('attendance_log')
            ->where('personnel_id', $personnelId)
            ->whereNull('time_out')
            ->orderByDesc('log_id')
            ->first();

        if (!$activeLog) {
            return redirect()
                ->route('admin.attendance.index')
                ->withErrors(['attendance' => 'No active personnel time-in record found.']);
        }

        DB::table('attendance_log')
            ->where('log_id', $activeLog->log_id)
            ->update([
                'time_out' => now()->format('H:i:s'),
            ]);

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Personnel time-out recorded successfully.');
    }

    /**
     * Records member time-in using a reference number from the kiosk.
     */
    public function timeInMember(Request $request)
    {
        $request->merge([
            'reference_number' => $this->normalizeReferenceNumber(
                $request->input('reference_number')
            ),
        ]);

        $validated = $request->validate([
            'reference_number' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
            ],
        ], $this->referenceNumberValidationMessages());

        // Matches normalized input while remaining compatible with older saved records.
        $member = DB::table('library_member')
            ->whereRaw('UPPER(TRIM(reference_number)) = ?', [
                $validated['reference_number'],
            ])
            ->first();

        if (!$member) {
            return redirect()
                ->route('attendance.kiosk')
                ->withErrors(['attendance' => 'Reference number not found.']);
        }

        // Prevents duplicate active time-in records for the same member.
        $activeLog = DB::table('attendance_log')
            ->where('member_id', $member->member_id)
            ->whereNull('time_out')
            ->orderByDesc('log_id')
            ->first();

        if ($activeLog) {
            return redirect()
                ->route('attendance.kiosk')
                ->withErrors(['attendance' => 'You already have an active time-in record. Please time out first.']);
        }

        DB::table('attendance_log')->insert([
            'role' => $member->member_role ?? 'member',
            'personnel_id' => null,
            'member_id' => $member->member_id,
            'visitor_id' => null,
            'log_date' => now()->toDateString(),
            'time_in' => now()->format('H:i:s'),
            'time_out' => null,
        ]);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Time-in recorded successfully.');
    }

    /**
     * Records member time-out using a reference number from the kiosk.
     */
    public function timeOutMember(Request $request)
    {
        $request->merge([
            'reference_number' => $this->normalizeReferenceNumber(
                $request->input('reference_number')
            ),
        ]);

        $validated = $request->validate([
            'reference_number' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/',
            ],
        ], $this->referenceNumberValidationMessages());

        // Matches normalized input while remaining compatible with older saved records.
        $member = DB::table('library_member')
            ->whereRaw('UPPER(TRIM(reference_number)) = ?', [
                $validated['reference_number'],
            ])
            ->first();

        if (!$member) {
            return redirect()
                ->route('attendance.kiosk')
                ->withErrors(['attendance' => 'Reference number not found.']);
        }

        // Finds the member's active log before recording the time-out.
        $activeLog = DB::table('attendance_log')
            ->where('member_id', $member->member_id)
            ->whereNull('time_out')
            ->orderByDesc('log_id')
            ->first();

        if (!$activeLog) {
            return redirect()
                ->route('attendance.kiosk')
                ->withErrors(['attendance' => 'No active time-in record found.']);
        }

        DB::table('attendance_log')
            ->where('log_id', $activeLog->log_id)
            ->update([
                'time_out' => now()->format('H:i:s'),
            ]);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Time-out recorded successfully.');
    }

    /**
     * Creates a visitor entry and starts its kiosk attendance log.
     */
    public function timeInVisitor(Request $request)
    {
        // Validate visitor names before creating the visitor attendance record.
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:3'],
        ], [
            'last_name.regex' => 'The last name may only contain letters and spaces.',
            'first_name.regex' => 'The first name may only contain letters and spaces.',
        ]);

        $middleInitial = $this->formatMiddleInitial($validated['middle_initial'] ?? null);

        // Uses raw SQL because the database column name `m.i.` contains dots.
        DB::insert(
            'INSERT INTO library_visitor (last_name, first_name, `m.i.`) VALUES (?, ?, ?)',
            [
                trim($validated['last_name']),
                trim($validated['first_name']),
                $middleInitial,
            ]
        );

        $visitorId = DB::getPdo()->lastInsertId();

        // Creates the visitor's active attendance log after registration.
        DB::table('attendance_log')->insert([
            'role' => 'visitor',
            'personnel_id' => null,
            'member_id' => null,
            'visitor_id' => $visitorId,
            'log_date' => now()->toDateString(),
            'time_in' => now()->format('H:i:s'),
            'time_out' => null,
        ]);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Visitor time-in recorded successfully.');
    }

    /**
     * Finds a visitor's active kiosk log and records time-out.
     */
    public function timeOutVisitor(Request $request)
    {
        // Validate visitor names before searching for an active log.
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'first_name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s]+$/'],
            'middle_initial' => ['nullable', 'string', 'max:3'],
        ], [
            'last_name.regex' => 'The last name may only contain letters and spaces.',
            'first_name.regex' => 'The first name may only contain letters and spaces.',
        ]);

        $middleInitial = $this->formatMiddleInitial($validated['middle_initial'] ?? null);

        // Use raw SQL for the legacy `m.i.` column because its name contains dots.
        $activeLog = DB::table('attendance_log as log')
            ->join('library_visitor as visitor', 'log.visitor_id', '=', 'visitor.visitor_id')
            ->where('visitor.last_name', trim($validated['last_name']))
            ->where('visitor.first_name', trim($validated['first_name']))
            ->where(function ($query) use ($middleInitial) {
                if ($middleInitial) {
                    $query->whereRaw('visitor.`m.i.` = ?', [$middleInitial]);
                } else {
                    $query->whereRaw('(visitor.`m.i.` IS NULL OR visitor.`m.i.` = "")');
                }
            })
            ->whereNull('log.time_out')
            ->orderByDesc('log.log_id')
            ->select('log.log_id')
            ->first();

        if (!$activeLog) {
            return redirect()
                ->route('attendance.kiosk')
                ->withErrors(['attendance' => 'No active visitor time-in record found.']);
        }

        DB::table('attendance_log')
            ->where('log_id', $activeLog->log_id)
            ->update([
                'time_out' => now()->format('H:i:s'),
            ]);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Visitor time-out recorded successfully.');
    }

    /**
     * Stores and compares reference numbers in one consistent format.
     */
    private function normalizeReferenceNumber(mixed $referenceNumber): string
    {
        return strtoupper(trim((string) $referenceNumber));
    }

    /**
     * Provides readable kiosk validation feedback for reference numbers.
     */
    private function referenceNumberValidationMessages(): array
    {
        return [
            'reference_number.required' => 'Please enter your registered reference number.',
            'reference_number.min' => 'The reference number must contain at least 4 characters.',
            'reference_number.max' => 'The reference number must not exceed 30 characters.',
            'reference_number.regex' => 'The reference number may only contain letters, numbers, hyphens, and underscores.',
        ];
    }

    /**
     * Normalizes middle initials before storing or matching names.
     */
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

    /**
     * Backward-compatible wrapper for member time-in by reference number.
     */
    public function timeInMemberByReference(Request $request)
    {
        return $this->timeInMember($request);
    }

    /**
     * Backward-compatible wrapper for member time-out by reference number.
     */
    public function timeOutMemberByReference(Request $request)
    {
        return $this->timeOutMember($request);
    }

    /**
     * Backward-compatible wrapper for visitor time-in by name.
     */
    public function timeInVisitorByName(Request $request)
    {
        return $this->timeInVisitor($request);
    }

    /**
     * Backward-compatible wrapper for visitor time-out by name.
     */
    public function timeOutVisitorByName(Request $request)
    {
        return $this->timeOutVisitor($request);
    }
}
