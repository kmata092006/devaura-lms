@extends('layouts.admin')

@section('title', 'Edit Library Member | UBNHS Library Management System')
@section('page-title', 'Edit Library Member')

@section('content')
    @php
        // Restore the saved role unless validation returns a newer choice.
        $selectedRole = old('member_role', $member->member_role);
        $hasSelectedRole = in_array($selectedRole, ['student', 'faculty'], true);
    @endphp

    {{-- Keeps the member form typography and responsive layout self-contained. --}}
    <style>
        .member-create-page {
            width: 100%;
            color: #0f172a;
            font-family: inherit;
            font-weight: 400;
        }

        .member-create-page input,
        .member-create-page select,
        .member-create-page button,
        .member-create-page a {
            font-family: inherit;
        }

        .member-create-header h2,
        .member-section-heading h3 {
            font-weight: 700;
        }

        .member-field-group label,
        .member-reference-rule strong,
        .member-back-button,
        .member-primary-button,
        .member-secondary-button {
            font-weight: 700;
        }

        .member-create-header p,
        .member-section-heading p,
        .member-field-help,
        .member-reference-rule {
            font-weight: 400;
        }

        .member-field-group input,
        .member-field-group select {
            font-weight: 400;
        }

        .member-field-group input::placeholder {
            font-weight: 400;
        }

        .member-create-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 22px;
            padding: 24px 26px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.055);
        }

        .member-create-header h2 {
            margin: 0 0 6px;
            color: #0f172a;
            font-size: 27px;
            font-weight: 700;
            letter-spacing: -0.45px;
        }

        .member-create-header p {
            margin: 0;
            color: #64748b;
            font-size: 15px;
            line-height: 1.55;
        }

        .member-back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 0 18px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: #e2e8f0;
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .member-back-button:hover {
            border-color: #94a3b8;
            background: #cbd5e1;
            transform: translateY(-1px);
        }

        .member-back-button svg {
            width: 17px;
            height: 17px;
        }

        .member-error-summary {
            width: min(1120px, 100%);
            margin-bottom: 20px;
            padding: 16px 18px;
            border: 1px solid #fecaca;
            border-radius: 16px;
            background: #fef2f2;
            color: #991b1b;
        }

        .member-error-summary-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .member-error-summary-header svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .member-error-summary ul {
            margin: 0;
            padding-left: 28px;
            font-size: 13px;
            font-weight: 400;
            line-height: 1.6;
        }

        .member-create-form {
            width: min(1120px, 100%);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            background: #ffffff;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.065);
        }

        .member-form-content {
            padding: 30px;
        }

        .member-form-section + .member-form-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
        }

        .member-section-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .member-section-number {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            flex-shrink: 0;
            border-radius: 14px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 14px;
            font-weight: 800;
        }

        .member-section-heading h3 {
            margin: 0 0 3px;
            color: #0f172a;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .member-section-heading p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.45;
        }

        .member-field-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .member-name-grid {
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) 150px;
        }

        .member-field-group {
            display: grid;
            align-content: start;
            gap: 8px;
        }

        .member-field-group.full-width {
            grid-column: 1 / -1;
        }

        .member-field-group label {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
        }

        .member-required-mark {
            margin-left: 3px;
            color: #dc2626;
        }

        .member-optional-mark {
            margin-left: 5px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .member-field-group input,
        .member-field-group select {
            width: 100%;
            min-height: 52px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 0 16px;
            background: #ffffff;
            color: #0f172a;
            font: inherit;
            font-size: 15px;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .member-field-group select {
            cursor: pointer;
        }

        .member-field-group input::placeholder {
            color: #7c8798;
            text-transform: none;
        }

        .member-field-group input:focus,
        .member-field-group select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .member-field-group input:disabled {
            border-color: #e2e8f0;
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }

        .member-field-group input.is-invalid,
        .member-field-group select.is-invalid {
            border-color: #ef4444;
            background: #fffafa;
        }

        .member-field-group input.is-invalid:focus,
        .member-field-group select.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.12);
        }

        .member-field-help {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.5;
        }

        .member-field-error {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin: 0;
            color: #dc2626;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.45;
        }

        .member-field-error svg {
            width: 15px;
            height: 15px;
            margin-top: 1px;
            flex-shrink: 0;
        }

        .member-reference-box {
            padding: 22px;
            border: 1px solid #bfdbfe;
            border-radius: 18px;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        }

        .member-reference-rule {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 12px;
            padding: 12px 14px;
            border: 1px solid #dbeafe;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.86);
            color: #475569;
            font-size: 12px;
            line-height: 1.5;
        }

        .member-reference-rule svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            color: #2563eb;
        }

        .member-reference-rule strong {
            display: block;
            margin-bottom: 2px;
            color: #1e3a8a;
            font-size: 12px;
            font-weight: 700;
        }

        .member-middle-initial input {
            text-align: left;
            text-transform: uppercase;
        }

        .member-middle-initial input::placeholder {
            text-align: left;
            text-transform: none;
        }

        .member-form-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            padding: 22px 30px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .member-primary-button,
        .member-secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            border-radius: 13px;
            padding: 0 20px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: 0.2s ease;
        }

        .member-primary-button {
            min-width: 150px;
            border: 1px solid #2563eb;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #ffffff;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.22);
        }

        .member-primary-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.28);
        }

        .member-primary-button:disabled {
            opacity: 0.68;
            cursor: wait;
            transform: none;
        }

        .member-secondary-button {
            border: 1px solid #cbd5e1;
            background: #e2e8f0;
            color: #0f172a;
        }

        .member-secondary-button:hover {
            border-color: #94a3b8;
            background: #cbd5e1;
        }

        .member-button-spinner {
            display: none;
            width: 16px;
            height: 16px;
            margin-right: 8px;
            border: 2px solid rgba(255, 255, 255, 0.45);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: member-spin 0.75s linear infinite;
        }

        .member-primary-button.is-submitting .member-button-spinner {
            display: inline-block;
        }

        @keyframes member-spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1000px) {
            .member-name-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .member-middle-initial {
                max-width: 190px;
            }
        }

        @media (max-width: 760px) {
            .member-create-header {
                flex-direction: column;
                align-items: stretch;
            }

            .member-back-button {
                width: 100%;
            }

            .member-form-content {
                padding: 22px 18px;
            }

            .member-field-grid,
            .member-name-grid {
                grid-template-columns: 1fr;
            }

            .member-middle-initial {
                max-width: none;
            }

            .member-reference-box {
                padding: 18px;
            }

            .member-form-footer {
                flex-direction: column-reverse;
                align-items: stretch;
                padding: 18px;
            }

            .member-primary-button,
            .member-secondary-button {
                width: 100%;
            }
        }
    </style>

    {{-- Updates a library member with role-specific reference-number rules. --}}
    <div class="member-create-page">
        <div class="member-create-header">
            <div>
                <h2>Edit Library Member</h2>
                <p>Update the selected student or faculty member record.</p>
            </div>

            <a
                href="{{ route('admin.members.index') }}"
                class="member-back-button"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M19 12H5"></path>
                    <path d="m12 19-7-7 7-7"></path>
                </svg>
                Back
            </a>
        </div>

        @if ($errors->any())
            <div class="member-error-summary" role="alert" aria-live="assertive">
                <div class="member-error-summary-header">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    Please correct the highlighted fields before saving.
                </div>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.members.update', $member->member_id) }}"
            id="member_edit_form"
            class="member-create-form"
        >
            @csrf
            @method('PUT')

            <div class="member-form-content">
                <section class="member-form-section" aria-labelledby="member-classification-heading">
                    <div class="member-section-heading">
                        <span class="member-section-number">01</span>

                        <div>
                            <h3 id="member-classification-heading">Member Classification</h3>
                            <p>Review or update whether the library member is a student or faculty member.</p>
                        </div>
                    </div>

                    <div class="member-field-grid">
                        <div class="member-field-group full-width">
                            <label for="member_role">
                                Member Role
                                <span class="member-required-mark" aria-hidden="true">*</span>
                            </label>

                            <select
                                name="member_role"
                                id="member_role"
                                class="@error('member_role') is-invalid @enderror"
                                aria-invalid="{{ $errors->has('member_role') ? 'true' : 'false' }}"
                                @if ($errors->has('member_role')) aria-describedby="member_role_error" @endif
                                required
                            >
                                <option value="">Select member role</option>
                                <option value="student" @selected($selectedRole === 'student')>
                                    Student
                                </option>
                                <option value="faculty" @selected($selectedRole === 'faculty')>
                                    Faculty
                                </option>
                            </select>

                            @error('member_role')
                                <p class="member-field-error" id="member_role_error">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="member-form-section" aria-labelledby="member-identification-heading">
                    <div class="member-section-heading">
                        <span class="member-section-number">02</span>

                        <div>
                            <h3 id="member-identification-heading">Member Identification</h3>
                            <p>Enter the official identifier required for the selected member role.</p>
                        </div>
                    </div>

                    <div class="member-reference-box">
                        <div class="member-field-group">
                            <label for="reference_number">
                                <span id="reference_number_label_text">Reference Number</span>
                                <span class="member-required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="text"
                                name="reference_number"
                                id="reference_number"
                                class="@error('reference_number') is-invalid @enderror"
                                value="{{ old('reference_number', $member->reference_number) }}"
                                placeholder="Select a member role first"
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                aria-invalid="{{ $errors->has('reference_number') ? 'true' : 'false' }}"
                                aria-describedby="reference_number_help @error('reference_number') reference_number_error @enderror"
                                @disabled(!$hasSelectedRole)
                                required
                            >

                            @error('reference_number')
                                <p class="member-field-error" id="reference_number_error">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror

                            <div class="member-reference-rule" id="reference_number_help">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>

                                <div>
                                    <strong id="reference_rule_title">Select a member role</strong>
                                    <span id="reference_rule_text">
                                        The required reference-number format will appear here.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="member-form-section" aria-labelledby="member-personal-heading">
                    <div class="member-section-heading">
                        <span class="member-section-number">03</span>

                        <div>
                            <h3 id="member-personal-heading">Personal Information</h3>
                            <p>Enter the member’s name using the format: Last Name, First Name, M.I.</p>
                        </div>
                    </div>

                    <div class="member-field-grid member-name-grid">
                        <div class="member-field-group">
                            <label for="last_name">
                                Last Name
                                <span class="member-required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="text"
                                name="last_name"
                                id="last_name"
                                class="@error('last_name') is-invalid @enderror"
                                value="{{ old('last_name', $member->last_name) }}"
                                maxlength="50"
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ .'-]+"
                                title="Letters, spaces, apostrophes, periods, and hyphens only. Numbers are not allowed."
                                placeholder="Enter Last Name"
                                autocomplete="family-name"
                                aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}"
                                @if ($errors->has('last_name')) aria-describedby="last_name_error" @endif
                                required
                            >

                            @error('last_name')
                                <p class="member-field-error" id="last_name_error">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="member-field-group">
                            <label for="first_name">
                                First Name
                                <span class="member-required-mark" aria-hidden="true">*</span>
                            </label>

                            <input
                                type="text"
                                name="first_name"
                                id="first_name"
                                class="@error('first_name') is-invalid @enderror"
                                value="{{ old('first_name', $member->first_name) }}"
                                maxlength="50"
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿÑñ .'-]+"
                                title="Letters, spaces, apostrophes, periods, and hyphens only. Numbers are not allowed."
                                placeholder="Enter First Name"
                                autocomplete="given-name"
                                aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}"
                                @if ($errors->has('first_name')) aria-describedby="first_name_error" @endif
                                required
                            >

                            @error('first_name')
                                <p class="member-field-error" id="first_name_error">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <div class="member-field-group member-middle-initial">
                            <label for="middle_initial">
                                M.I.
                                <span class="member-optional-mark">Optional</span>
                            </label>

                            <input
                                type="text"
                                name="middle_initial"
                                id="middle_initial"
                                class="@error('middle_initial') is-invalid @enderror"
                                value="{{ old('middle_initial', $member->middle_initial) }}"
                                maxlength="3"
                                pattern="(?:[A-Za-zÀ-ÖØ-öø-ÿÑñ]{1,3}|[A-Za-zÀ-ÖØ-öø-ÿÑñ]{1,2}\.)"
                                title="Enter one to three letters with an optional final period."
                                placeholder="Optional"
                                autocomplete="additional-name"
                                aria-invalid="{{ $errors->has('middle_initial') ? 'true' : 'false' }}"
                                @if ($errors->has('middle_initial')) aria-describedby="middle_initial_error" @endif
                            >

                            @error('middle_initial')
                                <p class="member-field-error" id="middle_initial_error">
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="member-form-footer">
                <a
                    href="{{ route('admin.members.index') }}"
                    class="member-secondary-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    id="update_member_button"
                    class="member-primary-button"
                >
                    <span class="member-button-spinner" aria-hidden="true"></span>
                    <span id="update_member_button_text">Update Member</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cache form controls used by dynamic member validation.
            const form = document.getElementById('member_edit_form');
            const roleSelect = document.getElementById('member_role');
            const referenceLabelText = document.getElementById('reference_number_label_text');
            const referenceInput = document.getElementById('reference_number');
            const referenceRuleTitle = document.getElementById('reference_rule_title');
            const referenceRuleText = document.getElementById('reference_rule_text');
            const lastNameInput = document.getElementById('last_name');
            const firstNameInput = document.getElementById('first_name');
            const middleInitialInput = document.getElementById('middle_initial');
            const updateButton = document.getElementById('update_member_button');
            const updateButtonText = document.getElementById('update_member_button_text');

            if (
                !form ||
                !roleSelect ||
                !referenceLabelText ||
                !referenceInput ||
                !referenceRuleTitle ||
                !referenceRuleText ||
                !updateButton ||
                !updateButtonText
            ) {
                return;
            }

            // Switch labels and browser validation based on member role.
            const configureReferenceField = () => {
                const selectedRole = roleSelect.value;

                if (selectedRole === 'student') {
                    referenceLabelText.textContent = 'Learner Reference Number (LRN)';
                    referenceInput.disabled = false;
                    referenceInput.placeholder = 'Enter 12-digit LRN';
                    referenceInput.inputMode = 'numeric';
                    referenceInput.setAttribute('minlength', '12');
                    referenceInput.setAttribute('maxlength', '12');
                    referenceInput.setAttribute('pattern', '[0-9]{12}');
                    referenceInput.setAttribute(
                        'title',
                        'Enter exactly 12 digits. Letters, spaces, and symbols are not allowed.'
                    );
                    referenceRuleTitle.textContent = 'Student LRN requirement';
                    referenceRuleText.textContent =
                        'Enter the student’s permanent 12-digit Learner Reference Number. Numbers only. Example: 407275150078.';
                    return;
                }

                if (selectedRole === 'faculty') {
                    referenceLabelText.textContent = 'Faculty ID';
                    referenceInput.disabled = false;
                    referenceInput.placeholder = 'Enter Faculty ID';
                    referenceInput.inputMode = 'text';
                    referenceInput.setAttribute('minlength', '4');
                    referenceInput.setAttribute('maxlength', '30');
                    referenceInput.setAttribute(
                        'pattern',
                        '(?=.*[A-Za-z])[A-Za-z0-9]+(?:[-_][A-Za-z0-9]+)*'
                    );
                    referenceInput.setAttribute(
                        'title',
                        'Use 4 to 30 characters containing at least one letter. Only letters, numbers, hyphens, and underscores are allowed.'
                    );
                    referenceRuleTitle.textContent = 'Faculty ID requirement';
                    referenceRuleText.textContent =
                        'Use 4 to 30 characters containing letters and numbers. Hyphens and underscores may appear between groups. Example: FAC-00125.';
                    return;
                }

                referenceLabelText.textContent = 'Reference Number';
                referenceInput.value = '';
                referenceInput.disabled = true;
                referenceInput.placeholder = 'Select a member role first';
                referenceInput.removeAttribute('minlength');
                referenceInput.removeAttribute('maxlength');
                referenceInput.removeAttribute('pattern');
                referenceInput.removeAttribute('title');
                referenceInput.removeAttribute('inputmode');
                referenceRuleTitle.textContent = 'Select a member role';
                referenceRuleText.textContent =
                    'The required reference-number format will appear here.';
            };

            // Enforce the selected role's reference-number format while typing.
            const sanitizeReferenceNumber = () => {
                if (roleSelect.value === 'student') {
                    referenceInput.value = referenceInput.value
                        .replace(/\D/g, '')
                        .slice(0, 12);
                    return;
                }

                if (roleSelect.value === 'faculty') {
                    referenceInput.value = referenceInput.value
                        .toUpperCase()
                        .replace(/[^A-Z0-9_-]/g, '')
                        .replace(/[-_]{2,}/g, (match) => match.charAt(0))
                        .replace(/^[-_]+/, '')
                        .slice(0, 30);
                    return;
                }

                referenceInput.value = '';
            };

            // Keep name inputs limited to readable name characters.
            const sanitizeName = (input) => {
                if (!input) {
                    return;
                }

                input.value = input.value.replace(/[^\p{L}\s.'’\-]/gu, '');
            };

            let previousRole = roleSelect.value;

            // Clear stale reference numbers when the member role changes.
            roleSelect.addEventListener('change', () => {
                const selectedRole = roleSelect.value;

                if (selectedRole !== previousRole) {
                    referenceInput.value = '';
                }

                previousRole = selectedRole;
                configureReferenceField();

                if (selectedRole !== '') {
                    referenceInput.focus();
                }
            });

            referenceInput.addEventListener('input', sanitizeReferenceNumber);

            referenceInput.addEventListener('blur', () => {
                sanitizeReferenceNumber();

                if (roleSelect.value === 'faculty') {
                    referenceInput.value = referenceInput.value.replace(/[-_]+$/, '');
                }
            });

            if (lastNameInput) {
                lastNameInput.addEventListener('input', () => sanitizeName(lastNameInput));
            }

            if (firstNameInput) {
                firstNameInput.addEventListener('input', () => sanitizeName(firstNameInput));
            }

            if (middleInitialInput) {
                // Store middle initials in a consistent uppercase format.
                middleInitialInput.addEventListener('input', () => {
                    middleInitialInput.value = middleInitialInput.value
                        .toUpperCase()
                        .replace(/[^\p{L}.]/gu, '')
                        .slice(0, 3);
                });
            }

            // Prevent duplicate submissions after the form passes validation.
            form.addEventListener('submit', () => {
                if (!form.checkValidity()) {
                    return;
                }

                updateButton.disabled = true;
                updateButton.classList.add('is-submitting');
                updateButtonText.textContent = 'Updating...';
            });

            configureReferenceField();
        });
    </script>
@endpush
