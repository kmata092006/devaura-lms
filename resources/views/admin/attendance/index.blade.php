@extends('layouts.admin')

@section('title', 'Attendance Logs | UBNHS Library Management System')
@section('page-title', 'Attendance Logs')

@section('content')
    {{-- Provides private personnel attendance actions and kiosk access. --}}
    <div class="page-actions">
        <div>
            <h2>Private Attendance Administration</h2>
            <p>View and manage attendance records privately. Public users should use the attendance kiosk.</p>
        </div>

        {{-- Open kiosk access only after the current personnel has timed in. --}}
        @if ($activePersonnelLog)
            <a href="{{ route('attendance.kiosk') }}" class="action-button" target="_blank">
                Open Public Kiosk
            </a>
        @endif
    </div>

    {{-- Displays attendance action feedback and validation errors. --}}
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

    {{-- Lets the signed-in personnel record their own time-in or time-out. --}}
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
            <p>Attendance kiosk users do not see names, personal records, dropdowns, or attendance logs.</p>

            <ul class="privacy-list">
                <li>Members type their own reference number.</li>
                <li>Visitors type their own name.</li>
                <li>Personnel attendance is private and account-based.</li>
                <li>Only authorized admin users can view attendance records.</li>
            </ul>
        </div>
    </div>

    {{-- Summarizes today's attendance queue and total historical records. --}}
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

    {{-- Filters attendance history by keyword, role, and date range. --}}
    <form method="GET" action="{{ route('admin.attendance.index') }}" class="filter-card attendance-filter-card">
        <input
            type="text"
            name="search"
            value="{{ $filters['search'] ?? '' }}"
            placeholder="Search by name, username, reference number, date, or role"
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
            id="attendance_date_from"
            value="{{ $filters['date_from'] ?? '' }}"
            min="{{ now()->toDateString() }}"
        >

        <input
            type="date"
            name="date_to"
            id="attendance_date_to"
            value="{{ $filters['date_to'] ?? '' }}"
            min="{{ $filters['date_from'] ?: now()->toDateString() }}"
        >

        <button type="submit">
            Search
        </button>

        <a href="{{ route('admin.attendance.index') }}" class="secondary-button">
            Reset
        </a>
    </form>

    <div class="system-records-shell attendance-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Attendance Records</h3>
                <p>{{ $attendanceLogs->total() }} {{ \Illuminate\Support\Str::plural('record', $attendanceLogs->total()) }} found</p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($attendanceLogs as $log)
                @php
                    // Build display initials and status styling for each attendance card.
                    $displayName = trim((string) $log->display_name);
                    $nameParts = preg_split('/\s+/', $displayName) ?: [];
                    $attendanceInitials = strtoupper(
                        substr($nameParts[0] ?? 'U', 0, 1)
                        . substr($nameParts[count($nameParts) - 1] ?? '', 0, 1)
                    );
                    $isInside = $log->status === 'Currently Inside';
                @endphp

                <article class="system-record-card {{ $isInside ? '' : 'is-completed' }}">
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div class="system-record-avatar {{ $isInside ? '' : 'is-green' }}" aria-hidden="true">
                                {{ $attendanceInitials ?: 'AT' }}
                            </div>

                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">Attendance User</span>
                                <h3>{{ $displayName ?: 'Unknown user' }}</h3>
                                <p>{{ $log->identifier ?: 'No reference available' }}</p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span class="system-record-badge is-neutral">{{ $log->record_type }}</span>
                            <span class="system-record-badge {{ $isInside ? '' : 'is-success' }}">
                                {{ $log->status }}
                            </span>
                        </div>
                    </header>

                    <div class="system-record-body attendance-record-grid">
                        <div class="system-record-field">
                            <span>Date</span>
                            <strong>{{ \Carbon\Carbon::parse($log->log_date)->format('M d, Y') }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Time In</span>
                            <strong>{{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Time Out</span>
                            <strong>{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : 'Not yet recorded' }}</strong>
                        </div>

                        <div class="system-record-field is-muted">
                            <span>Attendance Status</span>
                            <strong>{{ $log->status }}</strong>
                        </div>
                    </div>
                </article>
            @empty
                <div class="system-record-empty">
                    <strong>No attendance records found</strong>
                    Try adjusting the search or filter options.
                </div>
            @endforelse
        </div>
    </div>

    @if ($attendanceLogs->hasPages())
        <div class="pagination-wrapper">
            {{ $attendanceLogs->links() }}
        </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dateFrom = document.getElementById('attendance_date_from');
            const dateTo = document.getElementById('attendance_date_to');

            if (!dateFrom || !dateTo) {
                return;
            }

            const today = new Date().toISOString().split('T')[0];

            dateFrom.min = today;
            dateTo.min = dateFrom.value || today;

            // Keep the end date from being earlier than the selected start date.
            dateFrom.addEventListener('change', function () {
                dateTo.min = dateFrom.value || today;

                if (dateTo.value && dateTo.value < dateTo.min) {
                    dateTo.value = '';
                }
            });
        });
    </script>
    @endpush

@endsection
