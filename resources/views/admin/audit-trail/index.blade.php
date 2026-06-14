@extends('layouts.admin')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
    {{-- Introduces audit review and exposes print output for the current filters. --}}
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

    {{-- Filters audit entries by search text, actor role, action, module, and date. --}}
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

        <div class="system-records-shell audit-records-shell">
            <div class="system-records-meta">
                <div>
                    <h3>Audit Trail Records</h3>
                    <p>{{ $auditLogs->count() }} {{ \Illuminate\Support\Str::plural('activity', $auditLogs->count()) }} found</p>
                </div>
            </div>

            <div class="system-record-list">
                @forelse ($auditLogs as $log)
                    @php
                        // Build compact identity details for each audit activity card.
                        $auditUsername = $log->username ?: 'Unknown user';
                        $auditInitials = strtoupper(substr($auditUsername, 0, 2));
                    @endphp

                    <article class="system-record-card">
                        <header class="system-record-header">
                            <div class="system-record-identity">
                                <div class="system-record-avatar is-violet" aria-hidden="true">
                                    {{ $auditInitials ?: 'AU' }}
                                </div>
                                <div class="system-record-copy">
                                    <span class="system-record-eyebrow">System Activity</span>
                                    <h3>{{ $auditUsername }}</h3>
                                    @php
                                        // Reuse one parsed timestamp for header and detail display.
                                        $activityDateTime = \Carbon\Carbon::parse($log->created_at);
                                    @endphp
                                    <p class="audit-header-datetime">
                                        <span>{{ $activityDateTime->format('M d, Y') }}</span>
                                        <span aria-hidden="true">&bull;</span>
                                        <span>{{ $activityDateTime->format('h:i A') }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="system-record-badges">
                                <span class="system-record-badge">{{ $log->action_type }}</span>
                                <span class="system-record-badge is-neutral">
                                    {{ $log->personnel_role ? ucwords($log->personnel_role) : 'Unknown role' }}
                                </span>
                            </div>
                        </header>

                        <div class="system-record-body audit-record-grid">
                            <div class="system-record-field audit-datetime-field">
                                <span>Date and Time</span>
                                <div class="audit-datetime-split">
                                    <div>
                                        <small>Date</small>
                                        <strong>{{ $activityDateTime->format('M d, Y') }}</strong>
                                    </div>
                                    <div>
                                        <small>Time</small>
                                        <strong>{{ $activityDateTime->format('h:i A') }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="system-record-field">
                                <span>Module</span>
                                <strong>{{ $log->module_name }}</strong>
                            </div>

                            <div class="system-record-field">
                                <span>Action</span>
                                <strong>{{ $log->action_type }}</strong>
                            </div>

                            <div class="system-record-field is-primary">
                                <span>Activity Details</span>
                                <strong>{{ $log->display_details ?: 'No additional details' }}</strong>
                            </div>

                            <div class="system-record-field is-muted">
                                <span>IP Address</span>
                                <strong>{{ $log->ip_address ?: 'Not available' }}</strong>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="system-record-empty">
                        <strong>No audit trail records found</strong>
                        Adjust the filters to review other system activities.
                    </div>
                @endforelse
            </div>


        </div>
    </div>
@endsection
