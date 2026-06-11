@extends('layouts.admin')

@section('title', 'Role Permissions | UBNHS Library Management System')
@section('page-title', 'Role Permissions')

@section('content')
    <div class="page-actions">
        <div>
            <h2>Role Access Matrix</h2>
            <p>Grant or revoke module access for Staff and Student Staff roles.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.roles.update') }}" class="table-card">
        @csrf
        @method('PUT')

        <table class="data-table">
            <thead>
                <tr>
                    <th>Module / Feature</th>
                    @foreach ($roles as $role)
                        <th style="text-align: center;">{{ ucwords($role->personnel_role_name) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($matrix as $moduleName => $data)
                    <tr>
                        {{-- Regular text (no bold) --}}
                        <td style="color: #374151;">{{ $moduleName }}</td>
                        
                        @foreach ($roles as $role)
                            <td style="text-align: center;">
                                @php
                                    $roleId = $role->personnel_role_id;
                                    $isAllowed = in_array($roleId, $data['allowed_role_ids']);
                                    $isAdmin = ($role->personnel_role_name === 'admin');
                                @endphp

                                @if($isAdmin)
                                    {{-- Admin access is immutable/locked --}}
                                    <input type="checkbox" checked disabled>
                                @else
                                    <input type="checkbox" 
                                           name="permissions[{{ $roleId }}][]" 
                                           value="{{ $data['permission_id'] }}" 
                                           {{ $isAllowed ? 'checked' : '' }}>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="personnel-form-actions" style="margin-top: 20px;">
            <button type="submit" class="personnel-primary-button">Save Permissions</button>
        </div>
    </form>
@endsection