@extends('layouts.admin')

@section('title', 'Library Personnel')
@section('page-title', 'Library Personnel')

@section('content')
    <div class="personnel-header-card">
        <div>
            <h2>Library Personnel Records</h2>
            <p>Manage admin, staff, and student staff accounts, departments, and shifts.</p>
        </div>

        <a href="{{ route('admin.personnel.create') }}" class="personnel-primary-button">
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

    <form method="GET" action="{{ route('admin.personnel.index') }}" class="personnel-filter-card">
        <div class="personnel-filter-group personnel-search-wide">
            <label for="search">Search Personnel</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ request('search') }}"
                placeholder="Search by username, name, role, or department"
            >
        </div>

        <div class="personnel-filter-group">
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

        <div class="personnel-filter-group">
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

        <div class="personnel-filter-actions">
            <button type="submit" class="personnel-search-button">
                Search
            </button>

            <a href="{{ route('admin.personnel.index') }}" class="personnel-secondary-button">
                Reset
            </a>
        </div>
    </form>

    <div class="personnel-table-card">
        <table class="personnel-table">
            <thead>
                <tr>
                    <th>Personnel ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Shift Day</th>
                    <th>Shift Period</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($personnel as $person)
                    <tr>
                        <td>{{ $person->personnel_id }}</td>
                        <td class="personnel-username-cell">{{ $person->username }}</td>
                        <td>
                            <span class="personnel-role-pill">
                                {{ ucwords($person->personnel_role) }}
                            </span>
                        </td>
                        <td>{{ $person->full_name }}</td>
                        <td>{{ $person->department_name }}</td>
                        <td>{{ $person->shift_day ?? '-' }}</td>
                        <td>{{ $person->shift_period ?? '-' }}</td>
                        <td>
                            <div class="personnel-row-actions">
                                <a href="{{ route('admin.personnel.edit', $person->personnel_id) }}" class="personnel-edit-button">
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('admin.personnel.destroy', $person->personnel_id) }}"
                                    onsubmit="return confirm('Delete this personnel account?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="personnel-delete-button">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="personnel-empty-cell">
                            No library personnel found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $personnel->links() }}
    </div>
@endsection