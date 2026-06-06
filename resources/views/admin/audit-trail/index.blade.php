@extends('layouts.admin')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
    <div class="audit-hero">
        <div>
            <span class="audit-eyebrow">System Monitoring</span>
            <h2>Audit Trail</h2>
            <p>Track important system activities performed by library personnel.</p>
        </div>

        <button type="button" class="action-button" onclick="window.print()">
            Print Audit Trail
        </button>
    </div>

    <form method="GET" action="{{ route('admin.audit-trail.index') }}" class="audit-filter-card">
        <div class="audit-filter-group">
            <label for="search">Search</label>
            <input
                type="text"
                name="search"
                id="search"
                value="{{ $filters['search'] }}"
                placeholder="Search username, action, module, details, or IP address"
            >
        </div>

        <div class="audit-filter-group">
            <label for="personnel_role">Role</label>
            <select name="personnel_role" id="personnel_role">
                <option value="">All Roles</option>
                @foreach ($options['roles'] as $role)
                    <option value="{{ $role }}" {{ $filters['personnel_role'] === $role ? 'selected' : '' }}>
                        {{ ucwords($role) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="audit-filter-group">
            <label for="action_type">Action</label>
            <select name="action_type" id="action_type">
                <option value="">All Actions</option>
                @foreach ($options['actions'] as $action)
                    <option value="{{ $action }}" {{ $filters['action_type'] === $action ? 'selected' : '' }}>
                        {{ $action }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="audit-filter-group">
            <label for="module_name">Module</label>
            <select name="module_name" id="module_name">
                <option value="">All Modules</option>
                @foreach ($options['modules'] as $module)
                    <option value="{{ $module }}" {{ $filters['module_name'] === $module ? 'selected' : '' }}>
                        {{ $module }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="audit-filter-group">
            <label for="date_from">Date From</label>
            <input
                type="date"
                name="date_from"
                id="date_from"
                value="{{ $filters['date_from'] }}"
            >
        </div>

        <div class="audit-filter-group">
            <label for="date_to">Date To</label>
            <input
                type="date"
                name="date_to"
                id="date_to"
                value="{{ $filters['date_to'] }}"
            >
        </div>

        <div class="audit-filter-actions">
            <button type="submit">Generate</button>
            <a href="{{ route('admin.audit-trail.index') }}" class="secondary-button">Reset</a>
        </div>
    </form>

    <div class="audit-print-area">
        <div class="audit-print-header">
            <h2>Audit Trail Report</h2>
            <p>Generated on {{ now()->format('F d, Y h:i A') }}</p>
        </div>

        <div class="audit-table-card">
            <table class="audit-table">
                <thead>
                    <tr>
                        <th>Audit ID</th>
                        <th>Date & Time</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td>{{ $log->audit_id }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i A') }}</td>
                            <td>{{ $log->username ?? 'Unknown' }}</td>
                            <td>
                                <span class="audit-role-pill">
                                    {{ $log->personnel_role ? ucwords($log->personnel_role) : 'Unknown' }}
                                </span>
                            </td>
                            <td>
                                <span class="audit-action-pill">
                                    {{ $log->action_type }}
                                </span>
                            </td>
                            <td>{{ $log->module_name }}</td>
                            <td class="audit-details-cell">{{ $log->action_details ?? '-' }}</td>
                            <td>{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="audit-empty-cell">
                                No audit trail records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrapper audit-pagination">
            {{ $auditLogs->links() }}
        </div>
    </div>
@endsection