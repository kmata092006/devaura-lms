@extends('layouts.admin')

@section('title', 'Add Department')
@section('page-title', 'Add Department')

@section('content')
    @php
        // Restore preset or manual department choices after validation errors.
        $selectedDepartmentOption = old('department_option', '');
        $manualDepartmentValue = old('manual_department_name', '');
    @endphp

    {{-- Introduces the department creation workflow. --}}
    <div class="page-actions department-header-card">
        <div>
            <h2>Add Department</h2>
            <p>
                Select a standard subject department or enter a custom
                department name.
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

    {{-- Stores a preset department or a manually entered department name. --}}
    <form
        method="POST"
        action="{{ route('admin.departments.store') }}"
        class="department-form-card"
        id="department-create-form"
    >
        @csrf

        <div class="department-form-group">
            <label for="department_option">
                Select Department / Subject
            </label>

            <select
                name="department_option"
                id="department_option"
                required
            >
                <option value="" disabled
                    @selected($selectedDepartmentOption === '')>
                    Select a department
                </option>

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
                    Others
                </option>
            </select>

            <p class="department-form-help">
                Choose one of the standard subject departments. Select
                “Others” for a department not listed here.
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
                Custom Department Name
            </label>

            <input
                type="text"
                name="manual_department_name"
                id="manual_department_name"
                value="{{ $manualDepartmentValue }}"
                maxlength="100"
                placeholder="Enter custom department name"
                autocomplete="off"
                @if ($selectedDepartmentOption !== $manualOption)
                    disabled
                @endif
            >

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
                Save Department
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

            // Enable manual input only when the Others option is selected.
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
