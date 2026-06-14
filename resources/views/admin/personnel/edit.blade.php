@extends('layouts.admin')

@section('title', 'Edit Library Personnel | UBNHS Library Management System')
@section('page-title', 'Edit Library Personnel')

@section('content')
    {{-- Keeps middle-initial input alignment consistent with the personnel form. --}}
    <style>
        .improved-personnel-form .mi-field input {
            text-align: left;
        }

        .improved-personnel-form .mi-field input::placeholder {
            text-align: left;
            text-transform: none;
        }
    </style>

    @php
        // Restore saved assignment values unless validation returns new input.
        $savedStudentShiftPeriod = in_array(
            (string) $personnel->shift_period,
            ['Morning', 'Afternoon'],
            true
        )
            ? $personnel->shift_period
            : '';

        $studentShiftValue = old(
            'shift_period',
            $savedStudentShiftPeriod
        );

        $savedDepartmentId = old(
            'department_id',
            $personnel->department_id
        );

        $savedShiftDay = old(
            'shift_day',
            $personnel->shift_day
        );
    @endphp

    {{-- Updates personnel account details with role-specific assignment rules. --}}
    <div class="personnel-header-card">
        <div>
            <h2>Edit Library Personnel</h2>
            <p>Update account, department, and shift details.</p>
        </div>

        <a
            href="{{ route('admin.personnel.index') }}"
            class="personnel-secondary-button"
        >
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

    {{-- Main account form for identity, assignment, and optional password changes. --}}
    <form
        method="POST"
        action="{{ route(
            'admin.personnel.update',
            $personnel->personnel_id
        ) }}"
        class="personnel-form-card improved-personnel-form"
    >
        @csrf
        @method('PUT')

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
                    value="{{ old('last_name', $personnel->last_name) }}"
                    maxlength="50"
                    pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ .'-]+"
                    title="Letters, spaces, apostrophes, periods, and hyphens only. Numbers are not allowed."
                    placeholder="Enter Last Name"
                    autocomplete="family-name"
                    required
                >
            </div>

            <div class="personnel-form-group">
                <label for="first_name">First Name</label>

                <input
                    type="text"
                    name="first_name"
                    id="first_name"
                    value="{{ old('first_name', $personnel->first_name) }}"
                    maxlength="50"
                    pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ .'-]+"
                    title="Letters, spaces, apostrophes, periods, and hyphens only. Numbers are not allowed."
                    placeholder="Enter First Name"
                    autocomplete="given-name"
                    required
                >
            </div>

            <div class="personnel-form-group mi-field">
                <label for="middle_initial">M.I.</label>

                <input
                    type="text"
                    name="middle_initial"
                    id="middle_initial"
                    value="{{ old(
                        'middle_initial',
                        $personnel->middle_initial
                    ) }}"
                    maxlength="3"
                    pattern="(?:[A-Za-zÀ-ÖØ-öø-ÿÑñ]{1,3}|[A-Za-zÀ-ÖØ-öø-ÿÑñ]{1,2}\.)"
                    title="Enter one to three letters with an optional final period."
                    placeholder="Optional"
                    autocomplete="additional-name"
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

                <select
                    name="personnel_role"
                    id="personnel_role"
                    required
                >
                    <option value="">Select role</option>

                    @foreach ($roles as $role)
                        <option
                            value="{{ $role }}"
                            @selected(
                                old(
                                    'personnel_role',
                                    $personnel->personnel_role
                                ) === $role
                            )
                        >
                            {{ ucwords($role) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="personnel-form-group">
                <label for="department_id">Department</label>

                <select
                    name="department_id"
                    id="department_id"
                >
                    <option value="">Select department</option>

                    @foreach ($departments as $department)
                        <option
                            value="{{ $department->department_id }}"
                            @selected(
                                (string) old(
                                    'department_id',
                                    $personnel->department_id
                                )
                                ===
                                (string) $department->department_id
                            )
                        >
                            {{ $department->department_name }}
                        </option>
                    @endforeach
                </select>

                <small id="department-help">
                    Department is required for Admin and Staff.
                </small>
            </div>

            <div class="personnel-form-group">
                <label for="shift_day">Shift Day</label>

                <select
                    name="shift_day"
                    id="shift_day"
                >
                    <option value="">Select shift day</option>

                    @foreach ($shiftDays as $shiftDay)
                        <option
                            value="{{ $shiftDay }}"
                            @selected(
                                old(
                                    'shift_day',
                                    $personnel->shift_day
                                ) === $shiftDay
                            )
                        >
                            {{ $shiftDay }}
                        </option>
                    @endforeach
                </select>

                <small id="shift-day-help">
                    Shift day is required for Staff and Student Staff.
                </small>
            </div>

            <div class="personnel-form-group">
                <label for="shift_period">Shift Period</label>

                <select
                    name="shift_period"
                    id="shift_period"
                >
                    <option value="">Select shift period</option>

                    @foreach ($shiftPeriods as $shiftPeriod)
                        <option
                            value="{{ $shiftPeriod }}"
                            @selected(
                                old(
                                    'shift_period',
                                    $personnel->shift_period
                                ) === $shiftPeriod
                            )
                        >
                            {{ $shiftPeriod }}
                        </option>
                    @endforeach
                </select>

                <small id="shift-period-help">
                    Student Staff may select Morning or Afternoon.
                </small>
            </div>
        </div>

        <div class="personnel-account-box">
            <div class="personnel-section-title account-section-title">
                <span>03</span>

                <div>
                    <h3>Account Credentials</h3>
                    <p>
                        Update the username or set a new password.
                        Leave password fields blank to keep the current password.
                    </p>
                </div>
            </div>

            <div class="personnel-form-grid">
                <div class="personnel-form-group credential-field">
                    <label for="username">Username</label>

                    <input
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username', $personnel->username) }}"
                        minlength="4"
                        maxlength="50"
                        pattern="[A-Za-z0-9_-]+"
                        title="Letters, numbers, dashes, and underscores only. Spaces are not allowed."
                        placeholder="Example: staff_juan"
                        autocomplete="username"
                        required
                    >

                    <small>
                        Minimum 4 characters. Letters, numbers, dash, and underscore only.
                    </small>
                </div>

                <div class="personnel-form-group credential-field">
                    <label for="password">New Password</label>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        minlength="8"
                        placeholder="Leave blank to keep current password"
                        autocomplete="new-password"
                    >

                    <small>
                        Minimum 8 characters if changing the password.
                    </small>
                </div>

                <div class="personnel-form-group credential-field">
                    <label for="password_confirmation">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        minlength="8"
                        placeholder="Re-enter new password"
                        autocomplete="new-password"
                    >

                    <small>Must match the new password.</small>
                </div>
            </div>
        </div>

        <div class="personnel-form-actions">
            <button
                type="submit"
                class="personnel-primary-button"
            >
                Update Personnel
            </button>

            <a
                href="{{ route('admin.personnel.index') }}"
                class="personnel-secondary-button"
            >
                Cancel
            </a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cache controls used by role-based assignment behavior.
            const roleSelect = document.getElementById('personnel_role');
            const departmentSelect = document.getElementById('department_id');
            const departmentHelp = document.getElementById('department-help');
            const shiftDaySelect = document.getElementById('shift_day');
            const shiftDayHelp = document.getElementById('shift-day-help');
            const shiftPeriodSelect = document.getElementById('shift_period');
            const shiftPeriodHelp = document.getElementById('shift-period-help');
            const lastNameInput = document.getElementById('last_name');
            const firstNameInput = document.getElementById('first_name');
            const middleInitialInput = document.getElementById('middle_initial');
            const usernameInput = document.getElementById('username');

            if (
                !roleSelect ||
                !departmentSelect ||
                !departmentHelp ||
                !shiftDaySelect ||
                !shiftDayHelp ||
                !shiftPeriodSelect ||
                !shiftPeriodHelp
            ) {
                return;
            }

            // Remember previous choices so switching roles can restore them.
            let studentShiftValue =
                {{ Illuminate\Support\Js::from($studentShiftValue) }};

            let rememberedDepartmentId =
                {{ Illuminate\Support\Js::from(
                    (string) ($savedDepartmentId ?? '')
                ) }};

            let rememberedShiftDay =
                {{ Illuminate\Support\Js::from(
                    (string) ($savedShiftDay ?? '')
                ) }};

            function normalizeRole(value) {
                return String(value || '').trim().toLowerCase();
            }

            function optionExists(selectElement, value) {
                return value && Array.from(selectElement.options).some(
                    function (option) {
                        return option.value === value;
                    }
                );
            }

            function restoreDepartment() {
                if (optionExists(departmentSelect, rememberedDepartmentId)) {
                    departmentSelect.value = rememberedDepartmentId;
                }
            }

            function restoreShiftDay() {
                if (optionExists(shiftDaySelect, rememberedShiftDay)) {
                    shiftDaySelect.value = rememberedShiftDay;
                }
            }

            // Student Staff can choose only a half-day shift.
            function populateStudentShiftPeriods() {
                shiftPeriodSelect.innerHTML = `
                    <option value="">Select shift period</option>
                    <option value="Morning">Morning</option>
                    <option value="Afternoon">Afternoon</option>
                `;

                if (
                    studentShiftValue === 'Morning' ||
                    studentShiftValue === 'Afternoon'
                ) {
                    shiftPeriodSelect.value = studentShiftValue;
                }
            }

            function showStaffWholeDay() {
                shiftPeriodSelect.innerHTML = `
                    <option value="" selected>Whole Day</option>
                `;
            }

            function showNotApplicableShift() {
                shiftPeriodSelect.innerHTML = `
                    <option value="" selected>Not applicable</option>
                `;
            }

            // Apply department and shift requirements for the selected role.
            function updateAssignmentFields() {
                const role = normalizeRole(roleSelect.value);

                if (role === 'student staff') {
                    if (departmentSelect.value) {
                        rememberedDepartmentId = departmentSelect.value;
                    }

                    departmentSelect.value = '';
                    departmentSelect.disabled = true;
                    departmentSelect.required = false;
                    departmentHelp.textContent =
                        'Department is not required for Student Staff.';

                    shiftDaySelect.disabled = false;
                    shiftDaySelect.required = true;
                    restoreShiftDay();
                    shiftDayHelp.textContent =
                        'Select the assigned weekday for Student Staff.';

                    populateStudentShiftPeriods();
                    shiftPeriodSelect.disabled = false;
                    shiftPeriodSelect.required = true;
                    shiftPeriodHelp.textContent =
                        'Student Staff may select Morning or Afternoon.';

                    return;
                }

                if (role === 'staff') {
                    departmentSelect.disabled = false;
                    departmentSelect.required = true;
                    restoreDepartment();
                    departmentHelp.textContent =
                        'Department is required for Staff.';

                    shiftDaySelect.disabled = false;
                    shiftDaySelect.required = true;
                    restoreShiftDay();
                    shiftDayHelp.textContent =
                        'Select the assigned weekday for Staff.';

                    showStaffWholeDay();
                    shiftPeriodSelect.disabled = true;
                    shiftPeriodSelect.required = false;
                    shiftPeriodHelp.textContent =
                        'Staff is automatically assigned to Whole Day.';

                    return;
                }

                if (role === 'admin') {
                    departmentSelect.disabled = false;
                    departmentSelect.required = true;
                    restoreDepartment();
                    departmentHelp.textContent =
                        'Department is required for Admin.';

                    if (shiftDaySelect.value) {
                        rememberedShiftDay = shiftDaySelect.value;
                    }

                    shiftDaySelect.value = '';
                    shiftDaySelect.disabled = true;
                    shiftDaySelect.required = false;
                    shiftDayHelp.textContent =
                        'Shift day is not applicable to Admin.';

                    showNotApplicableShift();
                    shiftPeriodSelect.disabled = true;
                    shiftPeriodSelect.required = false;
                    shiftPeriodHelp.textContent =
                        'Shift period is not applicable to Admin.';

                    return;
                }

                departmentSelect.disabled = false;
                departmentSelect.required = false;
                departmentHelp.textContent = 'Select a role first.';

                shiftDaySelect.disabled = false;
                shiftDaySelect.required = false;
                shiftDayHelp.textContent = 'Select a role first.';

                populateStudentShiftPeriods();
                shiftPeriodSelect.disabled = false;
                shiftPeriodSelect.required = false;
                shiftPeriodHelp.textContent = 'Select a role first.';
            }

            // Keep names limited to readable name characters.
            function attachNameSanitizer(input) {
                if (!input) {
                    return;
                }

                input.addEventListener('input', function () {
                    input.value = input.value.replace(
                        /[^\p{L}\s.'’\-]/gu,
                        ''
                    );
                });
            }

            attachNameSanitizer(lastNameInput);
            attachNameSanitizer(firstNameInput);

            if (middleInitialInput) {
                // Keep middle initials short and letter-based.
                middleInitialInput.addEventListener('input', function () {
                    middleInitialInput.value = middleInitialInput.value
                        .replace(/[^\p{L}.]/gu, '')
                        .slice(0, 3);
                });
            }

            if (usernameInput) {
                // Restrict usernames to login-safe characters.
                usernameInput.addEventListener('input', function () {
                    usernameInput.value = usernameInput.value.replace(
                        /[^A-Za-z0-9_-]/g,
                        ''
                    );
                });
            }

            // Track choices that should return when the role changes back.
            departmentSelect.addEventListener('change', function () {
                if (departmentSelect.value) {
                    rememberedDepartmentId = departmentSelect.value;
                }
            });

            shiftDaySelect.addEventListener('change', function () {
                if (shiftDaySelect.value) {
                    rememberedShiftDay = shiftDaySelect.value;
                }
            });

            shiftPeriodSelect.addEventListener('change', function () {
                if (normalizeRole(roleSelect.value) === 'student staff') {
                    studentShiftValue = shiftPeriodSelect.value;
                }
            });

            roleSelect.addEventListener(
                'change',
                updateAssignmentFields
            );

            updateAssignmentFields();
        });
    </script>
@endsection
