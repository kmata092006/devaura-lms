@extends('layouts.admin')

@section('title', 'Attendance Logs')
@section('page-title', 'Attendance Logs')

@section('content')
    <div class="attendance-admin-hero">
        <div>
            <span class="attendance-admin-eyebrow">Private Admin Area</span>
            <h2>Attendance Management</h2>
            <p>
                Monitor attendance records privately. Public attendance logging is handled through the kiosk page.
            </p>
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
        <section class="attendance-admin-card primary">
            <div>
                <span class="attendance-admin-card-label">Personnel Attendance</span>
                <h3>My Attendance</h3>
                <p>
                    This controls only the currently logged-in account:
                    <strong>{{ session('full_name') }}</strong>.
                </p>
            </div>

            @if ($currentPersonnelActiveLog)
                <div class="attendance-admin-state active">
                    <strong>Currently Timed In</strong>
                    <span>Time in: {{ $currentPersonnelActiveLog->time_in }}</span>
                </div>

                <form method="POST" action="{{ route('admin.attendance.personnel.time-out') }}">
                    @csrf

                    <button type="submit" class="primary-button">
                        Time Out My Account
                    </button>
                </form>
            @else
                <div class="attendance-admin-state">
                    <strong>No Active Time-In</strong>
                    <span>You are not currently timed in.</span>
                </div>

                <form method="POST" action="{{ route('admin.attendance.personnel.time-in') }}">
                    @csrf

                    <button type="submit" class="primary-button">
                        Time In My Account
                    </button>
                </form>
            @endif
        </section>

        <section class="attendance-admin-card">
            <span class="attendance-admin-card-label">Privacy Reminder</span>
            <h3>Public users use the kiosk</h3>

            <ul class="attendance-admin-list">
                <li>Members type their own reference number.</li>
                <li>Visitors type their own name.</li>
                <li>No public dropdowns are shown.</li>
                <li>No attendance records are displayed publicly.</li>
            </ul>
        </section>
    </div>

    <div class="attendance-admin-stats">
        <div class="attendance-admin-stat">
            <strong>{{ $activeLogs->count() }}</strong>
            <span>Currently Inside</span>
        </div>

        <div class="attendance-admin-stat">
            <strong>{{ $logs->total() }}</strong>
            <span>Total Records</span>
        </div>

        <div class="attendance-admin-stat">
            <strong>{{ now()->format('M d, Y') }}</strong>
            <span>Current Date</span>
        </div>
    </div>

    <div class="section-heading">
        <div>
            <h2>Attendance Records</h2>
            <p>Search and filter attendance logs by name, role, date, reference number, or time.</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.attendance.index') }}" class="attendance-filter-form upgraded-filter-form">
        <div class="form-group">
            <label for="search">Search</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by name, role, log ID, reference number, date, or time"
            >
        </div>

        <div class="form-group">
            <label for="attendance_role">Role</label>
            <select name="role" id="attendance_role">
                <option value="">All Roles</option>

                @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                        {{ ucfirst($role) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="date">Date</label>
            <input
                type="date"
                name="date"
                id="date"
                value="{{ request('date') }}"
            >
        </div>

        <div class="attendance-filter-actions">
            <button type="submit">Apply Filters</button>
            <a href="{{ route('admin.attendance.index') }}" class="secondary-button">Reset</a>
        </div>
    </form>

    <div class="table-card modern-table-card">
        <table class="data-table attendance-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Person Type</th>
                    <th>Role</th>
                    <th>Name</th>
                    <th>Reference</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($logs as $log)
                    @php
                        $reference = '-';

                        if ($log->personnel_id) {
                            $personType = 'Personnel';

                            $name = trim(
                                $log->personnel_first_name . ' ' .
                                ($log->personnel_middle_initial ? $log->personnel_middle_initial . '. ' : '') .
                                $log->personnel_last_name
                            );
                        } elseif ($log->member_id) {
                            $personType = 'Member';
                            $reference = $log->member_reference_number ?? '-';

                            $name = trim(
                                $log->member_first_name . ' ' .
                                ($log->member_middle_initial ? $log->member_middle_initial . '. ' : '') .
                                $log->member_last_name
                            );
                        } else {
                            $personType = 'Visitor';

                            $name = trim(
                                $log->visitor_first_name . ' ' .
                                ($log->visitor_middle_initial ? $log->visitor_middle_initial . '. ' : '') .
                                $log->visitor_last_name
                            );
                        }
                    @endphp

                    <tr>
                        <td>{{ $log->log_id }}</td>
                        <td>{{ $personType }}</td>
                        <td>
                            <span class="role-pill">
                                {{ ucfirst($log->role) }}
                            </span>
                        </td>
                        <td class="attendance-name-cell">{{ $name }}</td>
                        <td>{{ $reference }}</td>
                        <td>{{ $log->log_date }}</td>
                        <td>{{ $log->time_in }}</td>
                        <td>{{ $log->time_out ?? '-' }}</td>
                        <td>
                            @if ($log->time_out)
                                <span class="status-label status-available">
                                    Completed
                                </span>
                            @else
                                <span class="status-label status-borrowed">
                                    Active
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

    <div class="pagination-wrapper">
        {{ $logs->links() }}
    </div>
@endsection