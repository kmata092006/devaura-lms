<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    private AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    public function index(Request $request)
    {
        $data = $this->attendanceService->getAttendancePageData(
            $request->query('search'),
            $request->query('role'),
            $request->query('date')
        );

        return view('admin.attendance.index', $data);
    }

    public function timeInPersonnel(Request $request)
    {
        $validated = $request->validate([
            'personnel_id' => ['required', 'integer', 'exists:library_personnel,personnel_id'],
        ]);

        $this->attendanceService->timeInPersonnel($validated['personnel_id']);

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Personnel time-in recorded successfully.');
    }

    public function timeInMember(Request $request)
    {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:library_member,member_id'],
        ]);

        $this->attendanceService->timeInMember($validated['member_id']);

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Library member time-in recorded successfully.');
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
            ->route('admin.attendance.index')
            ->with('success', 'Visitor time-in recorded successfully.');
    }

    public function timeOut(Request $request)
    {
        $validated = $request->validate([
            'log_id' => ['required', 'integer', 'exists:attendance_log,log_id'],
        ]);

        $this->attendanceService->timeOutLog($validated['log_id']);

        return redirect()
            ->route('admin.attendance.index')
            ->with('success', 'Time-out recorded successfully.');
    }
}