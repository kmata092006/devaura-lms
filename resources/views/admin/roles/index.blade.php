@extends('layouts.admin')

@section('title', 'Role Permissions | UBNHS Library Management System')
@section('page-title', 'Role Permissions')

@section('content')
    {{-- Shows the role-permission matrix used by module access checks. --}}
    <div class="page-actions">
        <div>
            <h2>Role Access Matrix</h2>
            <p>Grant or revoke module access for Staff and Student Staff roles.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Explains permissions that the system keeps locked for safety. --}}
    <div class="alert success" style="margin-bottom: 20px;">
        <p>
            Admin permissions are locked. Dashboard access is required for every role,
            while Role Permissions remains available to Admin only.
        </p>
    </div>

    {{-- Saves editable module permissions for non-admin roles. --}}
    <form method="POST" action="{{ route('admin.roles.update') }}" class="table-card">
        @csrf
        @method('PUT')

        <table class="data-table">
            <thead>
                <tr>
                    <th>Module / Feature</th>
                    @foreach ($roles as $role)
                        <th style="text-align: center;">
                            {{ ucwords($role->personnel_role_name) }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($matrix as $moduleName => $data)
                    <tr>
                        <td style="color: #374151;">{{ $moduleName }}</td>

                        @foreach ($roles as $role)
                            @php
                                // Lock admin, dashboard, and role-permission cells from manual edits.
                                $roleId = (int) $role->personnel_role_id;
                                $roleName = strtolower(trim($role->personnel_role_name));
                                $allowedRoleIds = array_map('intval', $data['allowed_role_ids']);
                                $isAllowed = in_array($roleId, $allowedRoleIds, true);
                                $isAdmin = $roleName === 'admin';
                                $isDashboard = $moduleName === 'Dashboard';
                                $isRolePermission = $moduleName === 'Role Permission';
                                $isLocked = $isAdmin || $isDashboard || $isRolePermission;
                                $isChecked = $isAdmin || $isDashboard || $isAllowed;

                                if ($isRolePermission && !$isAdmin) {
                                    $isChecked = false;
                                }
                            @endphp

                            <td style="text-align: center;">
                                <input
                                    type="checkbox"
                                    @unless ($isLocked)
                                        name="permissions[{{ $roleId }}][]"
                                        value="{{ $data['permission_id'] }}"
                                    @endunless
                                    {{ $isChecked ? 'checked' : '' }}
                                    {{ $isLocked ? 'disabled' : '' }}
                                    title="{{ $isLocked ? 'This permission is managed by the system.' : 'Toggle module access.' }}"
                                >
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="personnel-form-actions" style="margin-top: 20px;">
            <button type="submit" class="personnel-primary-button">
                Save Permissions
            </button>
        </div>
    </form>
@endsection
