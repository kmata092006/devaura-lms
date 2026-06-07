@extends('layouts.admin')

@section('title', 'Add Library Personnel | UBNHS Library Management System')
@section('page-title', 'Add Library Personnel')

@section('content')
    <div class="personnel-header-card">
        <div>
            <h2>Add Library Personnel</h2>
            <p>Create a new admin, staff, or student staff account.</p>
        </div>

        <a href="{{ route('admin.personnel.index') }}" class="personnel-secondary-button">
            Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.personnel.store') }}" class="personnel-form-card improved-personnel-form">
        @csrf

        <div class="personnel-section-title">
            <span>01</span>
            <div>
                <h3>Personal Information</h3>
                <p>Use the format: Last Name, First Name, M.I.</p>
            </div>
        </div>

        <div class="personnel-form-grid name-first-grid">
            <div class="personnel-form-group">
                <label for="last_name">Last Name</label>
                <input
                    type="text"
                    name="last_name"
                    id="last_name"
                    value="{{ old('last_name') }}"
                    maxlength="50"
                    placeholder="Enter Last Name"
                    required
                >
            </div>

            <div class="personnel-form-group">
                <label for="first_name">First Name</label>
                <input
                    type="text"
                    name="first_name"
                    id="first_name"
                    value="{{ old('first_name') }}"
                    maxlength="50"
                    placeholder="Enter First Name"
                    required
                >
            </div>

            <div class="personnel-form-group mi-field">
                <label for="middle_initial">M.I.</label>
                <input
                    type="text"
                    name="middle_initial"
                    id="middle_initial"
                    value="{{ old('middle_initial') }}"
                    maxlength="3"
                    placeholder="M.I."
                >
            </div>
        </div>

        <div class="personnel-section-title">
            <span>02</span>
            <div>
                <h3>Library Assignment</h3>
                <p>Assign role, department, and shift schedule.</p>
            </div>
        </div>

        <div class="personnel-form-grid">
            <div class="personnel-form-group">
                <label for="personnel_role">Role</label>
                <select name="personnel_role" id="personnel_role" required>
                    <option value="">Select role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" {{ old('personnel_role') === $role ? 'selected' : '' }}>
                            {{ ucwords($role) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="personnel-form-group">
                <label for="department_id">Department</label>
                <select name="department_id" id="department_id" required>
                    <option value="">Select department</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->department_id }}" {{ (string) old('department_id') === (string) $department->department_id ? 'selected' : '' }}>
                            {{ $department->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="personnel-form-group">
                <label for="shift_day">Shift Day</label>
                <select name="shift_day" id="shift_day" required>
                    <option value="">Select shift day</option>
                    @foreach ($shiftDays as $shiftDay)
                        <option value="{{ $shiftDay }}" {{ old('shift_day') === $shiftDay ? 'selected' : '' }}>
                            {{ $shiftDay }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="personnel-form-group">
                <label for="shift_period">Shift Period</label>
                <select name="shift_period" id="shift_period" required>
                    <option value="">Select shift period</option>
                    @foreach ($shiftPeriods as $shiftPeriod)
                        <option value="{{ $shiftPeriod }}" {{ old('shift_period') === $shiftPeriod ? 'selected' : '' }}>
                            {{ $shiftPeriod }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="personnel-account-box">
            <div class="personnel-section-title account-section-title">
                <span>03</span>
                <div>
                    <h3>Account Credentials</h3>
                    <p>Username and password are required for system access.</p>
                </div>
            </div>

            <div class="personnel-form-grid">
                <div class="personnel-form-group credential-field">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username') }}"
                        maxlength="50"
                        placeholder="Enter Username"
                        required
                    >
                    <small>Minimum 4 characters. Letters, numbers, dash, and underscore only.</small>
                </div>

                <div class="personnel-form-group credential-field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        minlength="8"
                        placeholder="Enter Password"
                        required
                    >
                    <small>Minimum 8 characters.</small>
                </div>

                <div class="personnel-form-group credential-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        minlength="8"
                        placeholder="Re-enter Password"
                        required
                    >
                    <small>Must match the password.</small>
                </div>
            </div>
        </div>

        <div class="personnel-form-actions">
            <button type="submit" class="personnel-primary-button">
                Save Personnel
            </button>

            <a href="{{ route('admin.personnel.index') }}" class="personnel-secondary-button">
                Cancel
            </a>
        </div>
    </form>
@endsection