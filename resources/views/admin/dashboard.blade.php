@extends('layouts.admin')

@php
    $roleName = session('personnel_role');
    $roleService = app(\App\Services\RoleService::class);
    $roleTitle = ucwords(str_replace(['assistant', 'Assistant'], ['staff', 'Staff'], $roleName ?? 'Admin'));
@endphp

@section('title', $roleTitle . ' Dashboard | UBNHS Library Management System')
@section('page-title', $roleTitle . ' Dashboard')

@section('content')
    <div class="dashboard-welcome-card">
        <div>
            <span class="dashboard-eyebrow">Library Administration Portal</span>
            <h2>Welcome, {{ session('first_name', 'System') }} {{ session('last_name', 'Admin') }}.</h2>
            <p>Select a module below to manage daily library operations.</p>
        </div>

        <div class="dashboard-text-logo">
            UBNHS
        </div>
    </div>

    <div class="dashboard-module-grid">
        {{-- 1. Attendance Logs --}}
        @if ($roleService->hasPermission($roleName, 'Attendance Logs'))
            <a href="{{ route('admin.attendance.index') }}" class="dashboard-module-card">
                <span class="module-icon">◷</span>
                <h3>Attendance Logs</h3>
                <p>Monitor time-in and time-out records for personnel, members, and visitors.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 2. Departments --}}
        @if ($roleService->hasPermission($roleName, 'Departments'))
            <a href="{{ route('admin.departments.index') }}" class="dashboard-module-card">
                <span class="module-icon">▦</span>
                <h3>Departments</h3>
                <p>Manage department records assigned to library personnel.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 3. Library Personnel --}}
        @if ($roleService->hasPermission($roleName, 'Library Personnel'))
            <a href="{{ route('admin.personnel.index') }}" class="dashboard-module-card">
                <span class="module-icon">☷</span>
                <h3>Library Personnel</h3>
                <p>Manage admin, staff, and student staff accounts.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 4. Library Members --}}
        @if ($roleService->hasPermission($roleName, 'Library Members'))
            <a href="{{ route('admin.members.index') }}" class="dashboard-module-card">
                <span class="module-icon">◉</span>
                <h3>Library Members</h3>
                <p>Manage student and faculty library members and details.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 5. Book Management --}}
        @if ($roleService->hasPermission($roleName, 'Book Management'))
            <a href="{{ route('admin.books.index') }}" class="dashboard-module-card">
                <span class="module-icon">▣</span>
                <h3>Book Management</h3>
                <p>Manage books, authors, publishers, genres, ISBN, and book statuses.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 6. Borrow Book --}}
        @if ($roleService->hasPermission($roleName, 'Borrow Book'))
            <a href="{{ route('admin.borrow.index') }}" class="dashboard-module-card">
                <span class="module-icon">⇢</span>
                <h3>Borrow Book</h3>
                <p>Create borrow transactions for registered library members.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 7. Return Book --}}
        @if ($roleService->hasPermission($roleName, 'Return Book'))
            <a href="{{ route('admin.return.index') }}" class="dashboard-module-card">
                <span class="module-icon">⇠</span>
                <h3>Return Book</h3>
                <p>Process returned, overdue, lost, and damaged book transactions.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 8. Book List / Search --}}
        @if ($roleService->hasPermission($roleName, 'Book List / Search'))
            <a href="{{ route('admin.book-list.index') }}" class="dashboard-module-card">
                <span class="module-icon">⌕</span>
                <h3>Book List / Search</h3>
                <p>View and search all books by title, ISBN, author, publisher, genre, and status.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 9. Reports --}}
        @if ($roleService->hasPermission($roleName, 'Report'))
            <a href="{{ route('admin.reports.index') }}" class="dashboard-module-card">
                <span class="module-icon">▤</span>
                <h3>Reports</h3>
                <p>Generate attendance, book status, personnel, member, visitor, and book reports.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 10. Audit Trail --}}
        @if ($roleService->hasPermission($roleName, 'Audit Trail'))
            <a href="{{ route('admin.audit-trail.index') }}" class="dashboard-module-card">
                <span class="module-icon">◎</span>
                <h3>Audit Trail</h3>
                <p>Track login activity, record changes, transactions, and system actions.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif

        {{-- 11. Role Permission --}}
        @if ($roleService->hasPermission($roleName, 'Role Permission'))
            <a href="{{ route('admin.roles.index') }}" class="dashboard-module-card">
                <span class="module-icon">⚙</span>
                <h3>Role & Permissions</h3>
                <p>View system access levels and visibility for library personnel.</p>
                <span class="dashboard-status available">Available</span>
            </a>
        @endif
    </div>
@endsection