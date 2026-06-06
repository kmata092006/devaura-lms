@extends('layouts.admin')

@section('title', 'Attendance Logs')
@section('page-title', 'Attendance Logs')

@section('content')
    <div class="attendance-hero">
        <div class="attendance-hero-overlay">
            <div class="attendance-live-date" id="attendanceDateText">
                Loading date...
            </div>

            <h2>TIME IN - TIME OUT</h2>
            <p>Library entrance attendance terminal for personnel, members, and visitors.</p>

            @if (session('success'))
                <div class="terminal-alert success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="terminal-alert error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="attendance-terminal">
                <div class="terminal-tabs">
                    <button type="button" class="terminal-tab active" data-attendance-tab="personnel">
                        Personnel
                    </button>
                    <button type="button" class="terminal-tab" data-attendance-tab="member">
                        Member
                    </button>
                    <button type="button" class="terminal-tab" data-attendance-tab="visitor">
                        Visitor
                    </button>
                    <button type="button" class="terminal-tab timeout-tab" data-attendance-tab="timeout">
                        Time Out
                    </button>
                </div>

                <div class="terminal-panel active" id="attendance-panel-personnel">
                    <form method="POST" action="{{ route('admin.attendance.personnel.time-in') }}" class="terminal-form">
                        @csrf

                        <select name="personnel_id" required>
                            <option value="">Select library personnel</option>
                            @foreach ($personnel as $person)
                                <option value="{{ $person->personnel_id }}">
                                    [ID: {{ $person->personnel_id }}]
                                    {{ $person->first_name }}
                                    {{ $person->middle_initial ? $person->middle_initial . '.' : '' }}
                                    {{ $person->last_name }}
                                    — {{ ucfirst($person->personnel_role) }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit">Time In</button>
                    </form>
                </div>

                <div class="terminal-panel" id="attendance-panel-member">
                    <form method="POST" action="{{ route('admin.attendance.member.time-in') }}" class="terminal-form">
                        @csrf

                        <select name="member_id" required>
                            <option value="">Select library member</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->member_id }}">
                                    [ID: {{ $member->member_id }}]
                                    {{ $member->first_name }}
                                    {{ $member->middle_initial ? $member->middle_initial . '.' : '' }}
                                    {{ $member->last_name }}
                                    — {{ ucfirst($member->member_role) }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit">Time In</button>
                    </form>
                </div>

                <div class="terminal-panel" id="attendance-panel-visitor">
                    <form method="POST" action="{{ route('admin.attendance.visitor.time-in') }}" class="terminal-visitor-form">
                        @csrf

                        <input
                            type="text"
                            name="last_name"
                            placeholder="Visitor last name"
                            maxlength="50"
                            required
                        >

                        <input
                            type="text"
                            name="first_name"
                            placeholder="Visitor first name"
                            maxlength="50"
                            required
                        >

                        <input
                            type="text"
                            name="middle_initial"
                            placeholder="M.I."
                            maxlength="3"
                        >

                        <button type="submit">Time In Visitor</button>
                    </form>
                </div>

                <div class="terminal-panel" id="attendance-panel-timeout">
                    <form method="POST" action="{{ route('admin.attendance.time-out') }}" class="terminal-form">
                        @csrf

                        <select name="log_id" required>
                            <option value="">Select active time-in record</option>

                            @foreach ($activeLogs as $activeLog)
                                @php
                                    if ($activeLog->personnel_id) {
                                        $name = trim($activeLog->personnel_first_name . ' ' . ($activeLog->personnel_middle_initial ? $activeLog->personnel_middle_initial . '. ' : '') . $activeLog->personnel_last_name);
                                    } elseif ($activeLog->member_id) {
                                        $name = trim($activeLog->member_first_name . ' ' . ($activeLog->member_middle_initial ? $activeLog->member_middle_initial . '. ' : '') . $activeLog->member_last_name);
                                    } else {
                                        $name = trim($activeLog->visitor_first_name . ' ' . ($activeLog->visitor_middle_initial ? $activeLog->visitor_middle_initial . '. ' : '') . $activeLog->visitor_last_name);
                                    }
                                @endphp

                                <option value="{{ $activeLog->log_id }}">
                                    [Log: {{ $activeLog->log_id }}]
                                    {{ $name }}
                                    — {{ ucfirst($activeLog->role) }}
                                    — Time In: {{ $activeLog->time_in }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit" {{ $activeLogs->isEmpty() ? 'disabled' : '' }}>
                            Time Out
                        </button>
                    </form>

                    @if ($activeLogs->isEmpty())
                        <p class="terminal-note">No active time-in records found.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="attendance-stats-row">
        <div class="attendance-stat-card">
            <span>{{ $activeLogs->count() }}</span>
            <p>Currently Inside</p>
        </div>

        <div class="attendance-stat-card">
            <span>{{ $logs->total() }}</span>
            <p>Total Attendance Records</p>
        </div>

        <div class="attendance-stat-card">
            <span>{{ now()->format('M d, Y') }}</span>
            <p>Current Attendance Date</p>
        </div>
    </div>

    <div class="section-heading">
        <div>
            <h2>Attendance Records</h2>
            <p>Search and filter all time-in and time-out records.</p>
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
                placeholder="Search by name, role, log ID, date, or time"
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
            <input type="date" name="date" id="date" value="{{ request('date') }}">
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
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    @php
                        if ($log->personnel_id) {
                            $personType = 'Personnel';
                            $name = trim($log->personnel_first_name . ' ' . ($log->personnel_middle_initial ? $log->personnel_middle_initial . '. ' : '') . $log->personnel_last_name);
                        } elseif ($log->member_id) {
                            $personType = 'Member';
                            $name = trim($log->member_first_name . ' ' . ($log->member_middle_initial ? $log->member_middle_initial . '. ' : '') . $log->member_last_name);
                        } else {
                            $personType = 'Visitor';
                            $name = trim($log->visitor_first_name . ' ' . ($log->visitor_middle_initial ? $log->visitor_middle_initial . '. ' : '') . $log->visitor_last_name);
                        }
                    @endphp

                    <tr>
                        <td>{{ $log->log_id }}</td>
                        <td>{{ $personType }}</td>
                        <td>
                            <span class="role-pill">{{ ucfirst($log->role) }}</span>
                        </td>
                        <td class="attendance-name-cell">{{ $name }}</td>
                        <td>{{ $log->log_date }}</td>
                        <td>{{ $log->time_in }}</td>
                        <td>{{ $log->time_out ?? '-' }}</td>
                        <td>
                            @if ($log->time_out)
                                <span class="status-label status-available">Completed</span>
                            @else
                                <span class="status-label status-borrowed">Active</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-table">
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

    <script>
        function updateAttendanceClock() {
            const dateElement = document.getElementById('attendanceDateText');

            if (!dateElement) {
                return;
            }

            const now = new Date();

            const formattedDate = now.toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });

            const formattedTime = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
            });

            dateElement.textContent = `${formattedDate} | ${formattedTime}`;
        }

        updateAttendanceClock();
        setInterval(updateAttendanceClock, 1000);

        document.querySelectorAll('[data-attendance-tab]').forEach((button) => {
            button.addEventListener('click', () => {
                const selectedTab = button.getAttribute('data-attendance-tab');

                document.querySelectorAll('[data-attendance-tab]').forEach((tab) => {
                    tab.classList.remove('active');
                });

                document.querySelectorAll('.terminal-panel').forEach((panel) => {
                    panel.classList.remove('active');
                });

                button.classList.add('active');

                const targetPanel = document.getElementById(`attendance-panel-${selectedTab}`);
                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
            });
        });
    </script>
@endsection