@extends('layouts.admin')

@section('title', 'Library Personnel | UBNHS Library Management System')
@section('page-title', 'Library Personnel')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Library Personnel Records</h2>
            <p>Manage admin, staff, and student staff accounts, departments, and shifts.</p>
        </div>

        <a href="{{ route('admin.personnel.create') }}" class="action-button">
            Add Personnel
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

    <form method="GET" action="{{ route('admin.personnel.index') }}" class="filter-card personnel-filter-card">
        <div class="filter-group">
            <label for="search">Search Personnel</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by username, name, role, or department"
            >
        </div>

        <div class="filter-group">
            <label for="role">Role</label>
            <select name="role" id="role">
                <option value="">All Roles</option>

                @foreach ($roles as $role)
                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                        {{ ucwords($role) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-group">
            <label for="department_id">Department</label>
            <select name="department_id" id="department_id">
                <option value="">All Departments</option>

                @foreach ($departments as $department)
                    <option value="{{ $department->department_id }}" {{ (string) request('department_id') === (string) $department->department_id ? 'selected' : '' }}>
                        {{ $department->department_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit">
                Search
            </button>

            <a href="{{ route('admin.personnel.index') }}" class="secondary-button">
                Reset
            </a>
        </div>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($personnel as $person)
                    <tr>
                        <td class="personnel-username-cell">{{ $person->username }}</td>
                        <td>{{ $person->first_name }} {{ $person->middle_initial ?? '' }} {{ $person->last_name }}</td>
                        <td><span class="personnel-role-pill">{{ ucwords($person->personnel_role) }}</span></td>
                        <td>{{ $person->department_name ?? 'N/A' }}</td>
                        <td>
                            <div class="table-action-group">
                                <a href="{{ route('admin.personnel.edit', $person->personnel_id) }}" class="edit-button">Edit</a>
                                @if ($person->personnel_id === session('personnel_id'))
                                    <span class="disabled-delete-button" title="Cannot delete self">Protected</span>
                                @else
                                    <form method="POST" action="{{ route('admin.personnel.destroy', $person->personnel_id) }}" class="inline-delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="delete-button" onclick="return confirm('Delete this personnel account?')">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-table">No library personnel found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($personnel->hasPages())
        <div class="pagination-wrapper">
            {{ $personnel->links() }}
        </div>
    @endif
@endsection