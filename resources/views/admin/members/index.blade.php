@extends('layouts.admin')

@section('title', 'Library Members')
@section('page-title', 'Library Members')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Library Member Records</h2>
            <p>Manage student and faculty library members.</p>
        </div>

        <a href="{{ route('admin.members.create') }}" class="action-button">Add Member</a>
    </div>

    @if (session('success'))
        <div class="alert success">
            {{ session('success') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.members.index') }}" class="filter-form">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search by ID, name, role, or middle initial"
        >

        <select name="role">
            <option value="">All Roles</option>
            <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Student</option>
            <option value="faculty" {{ request('role') === 'faculty' ? 'selected' : '' }}>Faculty</option>
        </select>

        <button type="submit">Search</button>
        <a href="{{ route('admin.members.index') }}" class="secondary-button">Reset</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Reference Number</th>
                    <th>Role</th>
                    <th>Name</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr>
                        <td>{{ $member->reference_number }}</td>
                        <td>{{ ucfirst($member->member_role) }}</td>
                        <td>
                            {{ $member->first_name }}
                            {{ $member->middle_initial ? $member->middle_initial . '.' : '' }}
                            {{ $member->last_name }}
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route('admin.members.edit', $member->member_id) }}" class="small-button edit">Edit</a>
                                <form method="POST" action="{{ route('admin.members.destroy', $member->member_id) }}" onsubmit="return confirm('Delete this member?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="small-button delete">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-table">No library members found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $members->links() }}
    </div>
@endsection