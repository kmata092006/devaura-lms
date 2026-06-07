<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    private AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $data = $this->attendanceService->getAdminPageData(
            $request->query('search'),
            $request->query('role'),
            $request->query('date')
        );

        return view('admin.attendance.index', $data);
    }

    public function kiosk()
    {
        return view('attendance.kiosk');
    }

    public function timeInCurrentPersonnel()
    {
        $this->attendanceService->timeInCurrentPersonnel();

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Your personnel time-in has been recorded.');
    }

    public function timeOutCurrentPersonnel()
    {
        $this->attendanceService->timeOutCurrentPersonnel();

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Your personnel time-out has been recorded.');
    }

    public function timeInMemberByReference(Request $request)
    {
        $validated = $request->validate([
            'member_reference' => ['required', 'string', 'max:50'],
        ]);

        $this->attendanceService->timeInMemberByReference($validated['member_reference']);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Time-in recorded successfully.');
    }

    public function timeOutMemberByReference(Request $request)
    {
        $validated = $request->validate([
            'member_reference' => ['required', 'string', 'max:50'],
        ]);

        $this->attendanceService->timeOutMemberByReference($validated['member_reference']);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Time-out recorded successfully.');
    }

    public function timeInVisitor(Request $request)
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:3'],
        ]);

        $this->attendanceService->timeInVisitor($validated);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Visitor time-in recorded successfully.');
    }

    public function timeOutVisitor(Request $request)
    {
        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_initial' => ['nullable', 'string', 'max:3'],
        ]);

        $this->attendanceService->timeOutVisitor($validated);

        return redirect()
            ->route('attendance.kiosk')
            ->with('success', 'Visitor time-out recorded successfully.');
    }

    public function timeInPersonnel(Request $request)
    {
        $personnelId = session('personnel_id');
        $personnelRole = session('personnel_role');

        if (!$personnelId) {
            return redirect()
                ->route('login')
                ->withErrors(['attendance' => 'Please log in first.']);
        }

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

    public function timeOutPersonnel(Request $request)
    {
        $personnelId = session('personnel_id');

        if (!$personnelId) {
            return redirect()
                ->route('login')
                ->withErrors(['attendance' => 'Please log in first.']);
        }

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
}