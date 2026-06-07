@extends('layouts.admin')

@section('title', 'Attendance Logs | UBNHS Library Management System')
@section('page-title', 'Attendance Logs')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Private Attendance Administration</h2>
            <p>View and manage attendance records privately. Public users should use the attendance kiosk.</p>
        </div>

        <a href="{{ route('attendance.kiosk') }}" class="action-button" target="_blank">
            Open Public Kiosk
        </a>
    </div>

    @if (session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="attendance-admin-grid">
        <div class="attendance-admin-card">
            <h3>My Personnel Attendance</h3>
            <p>This action only applies to the currently logged-in personnel account.</p>

            @if ($activePersonnelLog)
                <div class="attendance-status-box active">
                    You currently have an active personnel time-in record.
                </div>

                <form method="POST" action="{{ route('admin.attendance.personnel.time-out') }}">
                    @csrf
                    <button type="submit" class="attendance-warning-button">
                        Time Out My Account
                    </button>
                </form>
            @else
                <div class="attendance-status-box">
                    You currently have no active personnel time-in record.
                </div>

                <form method="POST" action="{{ route('admin.attendance.personnel.time-in') }}">
                    @csrf
                    <button type="submit" class="primary-button">
                        Time In My Account
                    </button>
                </form>
            @endif
        </div>

        <div class="attendance-admin-card">
            <h3>Privacy Notice</h3>
            <p>Attendance kiosk users do not see names, IDs, dropdowns, or attendance logs.</p>

            <ul class="privacy-list">
                <li>Members type their own reference number.</li>
                <li>Visitors type their own name.</li>
                <li>Personnel attendance is private and account-based.</li>
                <li>Only authorized admin users can view attendance records.</li>
            </ul>
        </div>
    </div>

    <div class="attendance-stat-grid">
        <div class="attendance-stat-card">
            <strong>{{ $currentlyInside }}</strong>
            <span>Currently Inside</span>
        </div>

        <div class="attendance-stat-card">
            <strong>{{ $totalAttendanceRecords }}</strong>
            <span>Total Attendance Records</span>
        </div>

        <div class="attendance-stat-card">
            <strong>{{ $currentAttendanceDate }}</strong>
            <span>Current Attendance Date</span>
        </div>
    </div>

    <div class="page-actions attendance-record-header">
        <div>
            <h2>Attendance Records</h2>
            <p>Search and filter time-in and time-out records.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.attendance.index') }}" class="filter-card attendance-filter-card">
        <input
            type="text"
            name="search"
            value="{{ $filters['search'] ?? '' }}"
            placeholder="Search by ID, name, username, reference number, or role"
        >

        <select name="role">
            <option value="">All Roles</option>
            <option value="admin" {{ ($filters['role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="staff" {{ ($filters['role'] ?? '') === 'staff' ? 'selected' : '' }}>Staff</option>
            <option value="student staff" {{ ($filters['role'] ?? '') === 'student staff' ? 'selected' : '' }}>Student Staff</option>
            <option value="Student" {{ ($filters['role'] ?? '') === 'Student' ? 'selected' : '' }}>Student</option>
            <option value="Faculty" {{ ($filters['role'] ?? '') === 'Faculty' ? 'selected' : '' }}>Faculty</option>
            <option value="visitor" {{ ($filters['role'] ?? '') === 'visitor' ? 'selected' : '' }}>Visitor</option>
        </select>

        <input
            type="date"
            name="date_from"
            value="{{ $filters['date_from'] ?? '' }}"
        >

        <input
            type="date"
            name="date_to"
            value="{{ $filters['date_to'] ?? '' }}"
        >

        <button type="submit">
            Search
        </button>

        <a href="{{ route('admin.attendance.index') }}" class="secondary-button">
            Reset
        </a>
    </form>

    <div class="table-card attendance-table-card">
        <table class="data-table attendance-data-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Type</th>
                    <th>Role</th>
                    <th>Identifier</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($attendanceLogs as $log)
                    <tr>
                        <td>{{ $log->log_id }}</td>
                        <td>{{ $log->record_type }}</td>
                        <td>{{ ucwords($log->role) }}</td>
                        <td>{{ $log->identifier }}</td>
                        <td>{{ $log->display_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($log->log_date)->format('M d, Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}</td>
                        <td>
                            @if ($log->time_out)
                                {{ \Carbon\Carbon::parse($log->time_out)->format('h:i A') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if ($log->status === 'Currently Inside')
                                <span class="status-badge active-status">
                                    Currently Inside
                                </span>
                            @else
                                <span class="status-badge completed-status">
                                    Completed
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="empty-table">
                            No attendance records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($attendanceLogs->hasPages())
        <div class="pagination-wrapper">
            {{ $attendanceLogs->links() }}
        </div>
    @endif
@endsection