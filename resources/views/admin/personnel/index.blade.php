@extends('layouts.admin')

@section('title', 'Library Personnel | UBNHS Library Management System')
@section('page-title', 'Library Personnel')

@section('content')
    @php
        // Protect built-in accounts even if older records lack the flag.
        $protectedUsernames = [
            'admin',
            'staff',
            'student_staff',
        ];

        $defaultAccountNames = [
            'admin' => 'System Administrator',
            'staff' => 'Library Staff',
            'student_staff' => 'Student Assistant',
        ];
    @endphp

    {{-- Keeps personnel cards, actions, and protected badges compact. --}}
    <style>
        .personnel-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .personnel-records-table {
            width: 100%;
            min-width: 1160px;
            border-collapse: collapse;
        }

        .personnel-records-table th {
            white-space: nowrap;
        }

        .personnel-records-table td {
            vertical-align: middle;
        }

        .personnel-username {
            color: #0f172a;
            font-weight: 900;
        }

        .personnel-role-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 30px;
            padding: 5px 13px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .personnel-muted-value {
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
        }

        .personnel-unassigned-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 29px;
            padding: 5px 11px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        /* Color-code shift-period badges by assignment type. */
        .personnel-shift-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 31px;
            padding: 6px 12px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .personnel-shift-badge svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .personnel-shift-morning {
            border-color: #fde68a;
            background: #fffbeb;
            color: #a16207;
        }

        .personnel-shift-afternoon {
            border-color: #fed7aa;
            background: #fff7ed;
            color: #c2410c;
        }

        .personnel-shift-whole-day {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .personnel-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
        }

        .personnel-edit-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 17px;
            border: 1px solid #fde68a;
            border-radius: 999px;
            background: #fef3c7;
            color: #92400e;
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            transition:
                background-color 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;
        }

        .personnel-edit-button:hover {
            border-color: #f59e0b;
            background: #fde68a;
            transform: translateY(-1px);
        }

        .personnel-delete-form {
            display: inline-flex;
            margin: 0;
        }

        .personnel-delete-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 17px;
            border: 1px solid #fecaca;
            border-radius: 999px;
            background: #fee2e2;
            color: #b91c1c;
            font-size: 13px;
            font-weight: 900;
            cursor: pointer;
        }

        .personnel-protected-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            min-height: 38px;
            padding: 8px 13px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #e2e8f0;
            color: #334155;
            font-size: 12px;
            font-weight: 900;
            cursor: help;
            white-space: nowrap;
        }

        .personnel-protected-badge:hover {
            border-color: #94a3b8;
            background: #cbd5e1;
            color: #0f172a;
        }

        .personnel-protected-badge svg {
            width: 17px;
            height: 17px;
            flex-shrink: 0;
        }

        .personnel-empty-state {
            padding: 34px 20px !important;
            color: #64748b;
            text-align: center;
            font-weight: 800;
        }

        @media (max-width: 900px) {
            .personnel-protected-badge span {
                display: none;
            }

            .personnel-protected-badge {
                width: 38px;
                padding: 8px;
            }
        }
    </style>

    {{-- Page actions provide quick access to personnel creation. --}}
    <div class="page-actions">
        <div>
            <h2>Library Personnel Records</h2>

            <p>
                Manage Admin, Staff, and Student Staff accounts,
                departments, and shift assignments.
            </p>
        </div>

        <a
            href="{{ route('admin.personnel.create') }}"
            class="action-button"
        >
            Add Personnel
        </a>
    </div>

    {{-- Shows the result of personnel account changes. --}}
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

    {{-- Filter personnel by identity, role, or department. --}}
    <form
        method="GET"
        action="{{ route('admin.personnel.index') }}"
        class="filter-card personnel-filter-card"
    >
        <div class="filter-group">
            <label for="search">Search Personnel</label>

            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                maxlength="100"
                placeholder="Search by username, name, role, or department"
            >
        </div>

        <div class="filter-group">
            <label for="role">Role</label>

            <select name="role" id="role">
                <option value="">All Roles</option>

                @foreach ($roles as $role)
                    <option
                        value="{{ $role }}"
                        @selected(request('role') === $role)
                    >
                        {{ ucwords(strtolower($role)) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="department_id">Department</label>

            <select name="department_id" id="department_id">
                <option value="">All Departments</option>

                @foreach ($departments as $department)
                    <option
                        value="{{ $department->department_id }}"
                        @selected(
                            (string) request('department_id')
                            ===
                            (string) $department->department_id
                        )
                    >
                        {{ $department->department_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit">
                Search
            </button>

            <a
                href="{{ route('admin.personnel.index') }}"
                class="secondary-button"
            >
                Reset
            </a>
        </div>
    </form>

    <div class="system-records-shell personnel-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Library Personnel Records</h3>
                <p>{{ $personnel->total() }} {{ \Illuminate\Support\Str::plural('account', $personnel->total()) }} found</p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($personnel as $person)
                    @php
                        // Normalize identity values before applying account safeguards.
                        $normalizedUsername = strtolower(
                            trim((string) $person->username)
                        );

                        $normalizedUsername = preg_replace(
                            '/[\s-]+/',
                            '_',
                            $normalizedUsername
                        );

                        $normalizedRole = strtolower(
                            trim((string) $person->personnel_role)
                        );

                        $fallbackProtectedAccount = in_array(
                            $normalizedUsername,
                            $protectedUsernames,
                            true
                        );

                        $isProtectedAccount =
                            (bool) (
                                $person->is_protected_account
                                ?? false
                            )
                            || $fallbackProtectedAccount;

                        $isCurrentAccount =
                            (int) $person->personnel_id
                            ===
                            (int) session('personnel_id');

                        $cannotDelete =
                            $isProtectedAccount
                            || $isCurrentAccount;

                        $protectedBadgeText = $isProtectedAccount
                            ? 'Protected'
                            : 'Current Account';

                        $lockTitle = $isProtectedAccount
                            ? 'This is a default system account and cannot be deleted.'
                            : 'The currently logged-in account cannot be deleted.';

                        // Prefer stable labels for default accounts, then saved names.
                        $displayName = $defaultAccountNames[
                            $normalizedUsername
                        ] ?? ($person->full_name ?? '');

                        if (!$displayName) {
                            $displayName = trim(
                                ($person->last_name ?? '') .
                                ', ' .
                                ($person->first_name ?? '')
                            );
                        }

                        $displayRole = ucwords($normalizedRole);

                        // Role rules decide whether department and shift fields apply.
                        if ($normalizedRole === 'student staff') {
                            $departmentDisplay = 'Not applicable';
                            $departmentType = 'muted';
                        } elseif (
                            empty($person->department_name)
                        ) {
                            $departmentDisplay = 'Not assigned';
                            $departmentType = 'unassigned';
                        } else {
                            $departmentDisplay =
                                $person->department_name;

                            $departmentType = 'normal';
                        }

                        if ($normalizedRole === 'admin') {
                            $shiftDayDisplay = 'Not applicable';
                            $shiftDayType = 'muted';
                        } elseif (empty($person->shift_day)) {
                            $shiftDayDisplay = 'Not assigned';
                            $shiftDayType = 'unassigned';
                        } else {
                            $shiftDayDisplay = $person->shift_day;
                            $shiftDayType = 'normal';
                        }

                        if ($normalizedRole === 'admin') {
                            $shiftPeriodDisplay = 'Not applicable';
                            $shiftPeriodType = 'muted';
                        } elseif ($normalizedRole === 'staff') {
                            $shiftPeriodDisplay = 'Whole Day';
                            $shiftPeriodType = 'whole-day';
                        } elseif (
                            $person->shift_period === 'Morning'
                        ) {
                            $shiftPeriodDisplay = 'Morning';
                            $shiftPeriodType = 'morning';
                        } elseif (
                            $person->shift_period === 'Afternoon'
                        ) {
                            $shiftPeriodDisplay = 'Afternoon';
                            $shiftPeriodType = 'afternoon';
                        } else {
                            $shiftPeriodDisplay = 'Not assigned';
                            $shiftPeriodType = 'unassigned';
                        }
                    @endphp

                <article class="system-record-card">
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div class="system-record-avatar" aria-hidden="true">
                                {{ strtoupper(substr($person->first_name ?? 'P', 0, 1) . substr($person->last_name ?? '', 0, 1)) }}
                            </div>

                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">Library Personnel</span>
                                <h3>{{ $displayName ?: 'Not assigned' }}</h3>
                                <p>{{ '@' . $person->username }}</p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span class="system-record-badge">{{ $displayRole }}</span>
                            @if ($cannotDelete)
                                <span class="system-record-badge is-neutral">{{ $protectedBadgeText }}</span>
                            @endif
                        </div>
                    </header>

                    <div class="system-record-body personnel-record-grid">
                        <div class="system-record-field">
                            <span>Username</span>
                            <strong>{{ $person->username }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Department</span>
                            <strong>{{ $departmentDisplay }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Shift Day</span>
                            <strong>{{ $shiftDayDisplay }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Shift Period</span>
                            <strong>{{ $shiftPeriodDisplay }}</strong>
                        </div>

                        {{-- Default and current accounts cannot be deleted from the list. --}}
                        <div class="system-record-actions-panel">
                            <span class="system-record-section-label">Actions</span>
                            <div class="system-record-actions personnel-actions">
                                <a
                                    href="{{ route('admin.personnel.edit', $person->personnel_id) }}"
                                    class="personnel-edit-button"
                                >
                                    Edit
                                </a>

                                @if ($cannotDelete)
                                    <span
                                        class="personnel-protected-badge"
                                        title="{{ $lockTitle }}"
                                        aria-label="{{ $lockTitle }}"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path d="M7.75 10V7.75C7.75 5.4 9.65 3.5 12 3.5C14.35 3.5 16.25 5.4 16.25 7.75V10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            <rect x="5.5" y="10" width="13" height="10" rx="2.25" stroke="currentColor" stroke-width="1.8" />
                                        </svg>
                                        <span>{{ $protectedBadgeText }}</span>
                                    </span>
                                @else
                                    <form
                                        method="POST"
                                        action="{{ route('admin.personnel.destroy', $person->personnel_id) }}"
                                        class="personnel-delete-form"
                                        onsubmit="return confirm('Are you sure you want to delete this personnel account?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="personnel-delete-button">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="system-record-empty">
                    <strong>No library personnel records found</strong>
                    Add personnel or adjust the current filters.
                </div>
            @endforelse
        </div>
    </div>

    @if ($personnel->hasPages())
        <div class="pagination-wrapper">
            {{ $personnel->links() }}
        </div>
    @endif
@endsection
