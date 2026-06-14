@extends('layouts.admin')

@section('title', 'Library Members')
@section('page-title', 'Library Members')

@section('content')
    {{-- Keeps member list actions and protected-delete badges readable on small screens. --}}
    <style>
        .member-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .member-records-table {
            width: 100%;
            min-width: 820px;
            border-collapse: collapse;
        }

        .member-records-table td {
            vertical-align: middle;
        }

        .member-reference-number {
            color: #0f172a;
            font-weight: 800;
        }

        .member-role-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 29px;
            padding: 5px 12px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .member-actions {
            display: flex;
            align-items: center;
            gap: 9px;
            white-space: nowrap;
        }

        .member-delete-form {
            display: inline-flex;
            margin: 0;
        }

        .member-cannot-delete-badge {
            display: inline-flex;
            position: relative;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 32px;
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #e2e8f0;
            color: #475569;
            font-size: 12px;
            font-weight: 900;
            cursor: help;
            white-space: nowrap;
        }

        .member-cannot-delete-badge:hover,
        .member-cannot-delete-badge:focus-visible {
            border-color: #94a3b8;
            background: #cbd5e1;
            color: #0f172a;
            outline: none;
        }

        .member-cannot-delete-badge svg {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .member-empty-state {
            padding: 34px 20px !important;
            color: #64748b;
            text-align: center;
            font-weight: 800;
        }

        @media (max-width: 800px) {
            .member-cannot-delete-badge span {
                display: none;
            }

            .member-cannot-delete-badge {
                width: 34px;
                padding: 6px;
            }
        }
    </style>

    {{-- Page actions provide quick access to member creation. --}}
    <div class="page-actions">
        <div>
            <h2>Library Member Records</h2>
            <p>Manage student and faculty library members.</p>
        </div>

        <a
            href="{{ route('admin.members.create') }}"
            class="action-button"
        >
            Add Member
        </a>
    </div>

    {{-- Shows the result of create, update, or delete actions. --}}
    @if (session('success'))
        <div class="alert success" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert error" role="alert">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filter members by reference number, name, or role. --}}
    <form
        method="GET"
        action="{{ route('admin.members.index') }}"
        class="filter-form"
    >
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            maxlength="100"
            placeholder="Search by reference number, name, or role"
        >

        <select name="role">
            <option value="">All Roles</option>

            <option
                value="student"
                {{ request('role') === 'student' ? 'selected' : '' }}
            >
                Student
            </option>

            <option
                value="faculty"
                {{ request('role') === 'faculty' ? 'selected' : '' }}
            >
                Faculty
            </option>
        </select>

        <button type="submit">Search</button>

        <a
            href="{{ route('admin.members.index') }}"
            class="secondary-button"
        >
            Reset
        </a>
    </form>

    <div class="system-records-shell member-records-shell">
        <div class="system-records-meta">
            <div>
                <h3>Library Member Records</h3>
                <p>{{ $members->total() }} {{ \Illuminate\Support\Str::plural('member', $members->total()) }} found</p>
            </div>
        </div>

        <div class="system-record-list">
            @forelse ($members as $member)
                    @php
                        // Borrowing history protects the member record; attendance history does not.
                        $borrowTransactionCount = (int) (
                            $member->borrow_transaction_count ?? 0
                        );

                        $attendanceLogCount = (int) (
                            $member->attendance_log_count ?? 0
                        );

                        $hasBorrowingRecords = $borrowTransactionCount > 0;
                        $hasAttendanceRecords = $attendanceLogCount > 0;

                        $borrowingRecordText = $borrowTransactionCount === 1
                            ? '1 borrowing transaction'
                            : $borrowTransactionCount . ' borrowing transactions';

                        $cannotDeleteMessage =
                            'This member cannot be deleted because the account is linked to '
                            . $borrowingRecordText
                            . '.';

                        $deleteConfirmation = $hasAttendanceRecords
                            ? 'Permanently delete this library member and all related attendance records? This action cannot be undone.'
                            : 'Permanently delete this library member? This action cannot be undone.';

                        // Normalize optional middle initials for display names.
                        $middleInitial = trim(
                            (string) ($member->middle_initial ?? '')
                        );

                        $formattedMiddleInitial = $middleInitial !== ''
                            ? rtrim($middleInitial, '.') . '.'
                            : '';
                    @endphp

                @php
                    // Prepare compact identity text for each record card.
                    $memberDisplayName = trim(implode(' ', array_filter([
                        $member->first_name,
                        $formattedMiddleInitial,
                        $member->last_name,
                    ])));
                    $memberInitials = strtoupper(
                        substr($member->first_name ?? 'M', 0, 1)
                        . substr($member->last_name ?? '', 0, 1)
                    );
                @endphp

                <article class="system-record-card">
                    <header class="system-record-header">
                        <div class="system-record-identity">
                            <div class="system-record-avatar is-violet" aria-hidden="true">
                                {{ $memberInitials ?: 'LM' }}
                            </div>

                            <div class="system-record-copy">
                                <span class="system-record-eyebrow">Library Member</span>
                                <h3>{{ $memberDisplayName }}</h3>
                                <p>{{ ucfirst($member->member_role) }} account</p>
                            </div>
                        </div>

                        <div class="system-record-badges">
                            <span class="system-record-badge">{{ ucfirst($member->member_role) }}</span>
                            @if ($hasBorrowingRecords)
                                <span class="system-record-badge is-neutral">Borrowing History</span>
                            @elseif ($hasAttendanceRecords)
                                <span class="system-record-badge is-neutral">Attendance History</span>
                            @endif
                        </div>
                    </header>

                    <div class="system-record-body member-record-grid">
                        <div class="system-record-field is-primary">
                            <span>{{ strtolower($member->member_role) === 'student' ? 'Learner Reference Number' : 'Faculty ID' }}</span>
                            <strong>{{ $member->reference_number }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Borrowing Records</span>
                            <strong>{{ $borrowTransactionCount }}</strong>
                        </div>

                        <div class="system-record-field">
                            <span>Attendance Records</span>
                            <strong>{{ $attendanceLogCount }}</strong>
                        </div>

                        {{-- Attendance history may be removed with the member; borrowing history blocks deletion. --}}
                        <div class="system-record-actions-panel">
                            <span class="system-record-section-label">Actions</span>
                            <div class="system-record-actions member-actions">
                                <a href="{{ route('admin.members.edit', $member->member_id) }}" class="small-button edit">Edit</a>

                                @if ($hasBorrowingRecords)
                                    <span
                                        class="member-cannot-delete-badge"
                                        title="{{ $cannotDeleteMessage }}"
                                        aria-label="{{ $cannotDeleteMessage }}"
                                        tabindex="0"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="4" y="10" width="16" height="10" rx="2"></rect>
                                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                                        </svg>
                                        <span>Cannot Delete</span>
                                    </span>
                                @else
                                    <form
                                        method="POST"
                                        action="{{ route('admin.members.destroy', $member->member_id) }}"
                                        class="member-delete-form"
                                        onsubmit='return confirm(@js($deleteConfirmation));'
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="small-button delete">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="system-record-empty">
                    <strong>No library members found</strong>
                    Add a member or adjust the search filters.
                </div>
            @endforelse
        </div>
    </div>

    <div class="pagination-wrapper">
        {{ $members->links() }}
    </div>
@endsection
