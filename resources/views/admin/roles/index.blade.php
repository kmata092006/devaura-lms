@extends('layouts.admin')

@section('title', 'Role Permissions | UBNHS Library Management System')
@section('page-title', 'Role Permissions')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Role Access Matrix</h2>
            <p>View the current system access levels and module visibility for each library personnel role.</p>
        </div>
    </div>

    {{-- Simple note explaining the current static nature of the permissions --}}
    <div class="dashboard-intro" style="padding: 16px 24px; display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 24px;">ℹ️</span>
        <p style="margin: 0; color: #374151;">
            <strong>System Security Note:</strong> Role access levels are currently firmly set in the system architecture to maintain strict data integrity. 
        </p>
    </div>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Module / Feature</th>
                    <th style="text-align: center;">Admin</th>
                    <th style="text-align: center;">Staff</th>
                    <th style="text-align: center;">Student Staff</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matrix as $module => $allowedRoles)
                    <tr>
                        <td style="font-weight: 800; color: #111827;">{{ $module }}</td>
                        
                        {{-- Admin Column --}}
                        <td style="text-align: center;">
                            @if(in_array('admin', $allowedRoles))
                                <span class="status-label status-available" style="color: green;">Access</span>
                            @else
                                <span class="status-label status-lost" style="color: red;">Restricted</span>
                            @endif
                        </td>

                        {{-- Staff Column --}}
                        <td style="text-align: center;">
                            @if(in_array('staff', $allowedRoles))
                                <span class="status-label status-available" style="color: green;">Access</span>
                            @else
                                <span class="status-label status-lost" style="color: red;">Restricted</span>
                            @endif
                        </td>

                        {{-- Student Staff Column --}}
                        <td style="text-align: center;">
                            @if(in_array('student staff', $allowedRoles))
                                <span class="status-label status-available" style="color: green;">Access</span>
                            @else
                                <span class="status-label status-lost" style="color: red;">Restricted</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection