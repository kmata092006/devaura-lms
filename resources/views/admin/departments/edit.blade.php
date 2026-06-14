@extends('layouts.admin')

@section('title', 'Edit Department')
@section('page-title', 'Edit Department')

@section('content')
    @php
        // Select the matching preset or fall back to manual input for custom names.
        $defaultDepartmentOption = in_array(
            $department->department_name,
            $presetDepartments,
            true
        )
            ? $department->department_name
            : $manualOption;

        $selectedDepartmentOption = old(
            'department_option',
            $defaultDepartmentOption
        );

        $manualDepartmentValue = old(
            'manual_department_name',
            $selectedDepartmentOption === $manualOption
                ? $department->department_name
                : ''
        );
    @endphp

    {{-- Introduces the department update workflow. --}}
    <div class="page-actions department-header-card">
        <div>
            <h2>Edit Department</h2>
            <p>
                Update the selected department using a preset subject
                or a manually entered department name.
            </p>
        </div>

        <a
            href="{{ route('admin.departments.index') }}"
            class="department-reset-button"
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

    {{-- Updates the selected department with preset or manual naming rules. --}}
    <form
        method="POST"
        action="{{ route(
            'admin.departments.update',
            $department->department_id
        ) }}"
        class="department-form-card"
        id="department-edit-form"
        onsubmit="return confirm(
            'Are you sure you want to update this department?'
        );"
    >
        @csrf
        @method('PUT')

        <div class="department-form-group">
            <label for="department_option">
                Select Department / Subject
            </label>

            <select
                name="department_option"
                id="department_option"
                required
            >
                @foreach ($presetDepartments as $presetDepartment)
                    <option
                        value="{{ $presetDepartment }}"
                        @selected(
                            $selectedDepartmentOption === $presetDepartment
                        )
                    >
                        {{ $presetDepartment }}
                    </option>
                @endforeach

                <option
                    value="{{ $manualOption }}"
                    @selected(
                        $selectedDepartmentOption === $manualOption
                    )
                >
                    Other / Manual Input
                </option>
            </select>

            <p class="department-form-help">
                Select a standard subject or choose manual input to
                enter a custom department name.
            </p>

            @error('department_option')
                <p class="department-field-error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div
            class="department-form-group department-manual-wrapper"
            id="manual-department-wrapper"
            @if ($selectedDepartmentOption !== $manualOption)
                hidden
            @endif
        >
            <label for="manual_department_name">
                Manual Department Name
            </label>

            <input
                type="text"
                name="manual_department_name"
                id="manual_department_name"
                value="{{ $manualDepartmentValue }}"
                maxlength="100"
                placeholder="Example: ICT Department"
                autocomplete="off"
                @if ($selectedDepartmentOption !== $manualOption)
                    disabled
                @endif
            >

            <p class="department-form-help">
                Allowed characters: letters, numbers, spaces,
                hyphen (-), slash (/), period (.), and underscore (_).
            </p>

            @error('manual_department_name')
                <p class="department-field-error">
                    {{ $message }}
                </p>
            @enderror

            @error('department_name')
                <p class="department-field-error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="department-form-actions">
            <button
                type="submit"
                class="department-primary-button"
            >
                Update Department
            </button>

            <a
                href="{{ route('admin.departments.index') }}"
                class="department-reset-button"
            >
                Cancel
            </a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const departmentOption =
                document.getElementById('department_option');

            const manualWrapper =
                document.getElementById('manual-department-wrapper');

            const manualInput =
                document.getElementById('manual_department_name');

            const manualOption = @json($manualOption);

            if (
                !departmentOption ||
                !manualWrapper ||
                !manualInput
            ) {
                return;
            }

            // Enable manual input only when manual department naming is selected.
            function updateManualDepartmentField() {
                const isManual =
                    departmentOption.value === manualOption;

                manualWrapper.hidden = !isManual;
                manualInput.disabled = !isManual;
                manualInput.required = isManual;
            }

            departmentOption.addEventListener(
                'change',
                updateManualDepartmentField
            );

            updateManualDepartmentField();
        });
    </script>
@endsection
