@extends('layouts.admin')

@section('title', 'Add Library Member')
@section('page-title', 'Add Library Member')

@section('content')
    <div class="form-card">
        <div class="form-card-header">
            <h2>Add New Library Member</h2>
            <p>Enter the details of a student or faculty library member.</p>
        </div>

        @if ($errors->any())
            <div class="alert error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.members.store') }}" class="record-form">
            @csrf

            <div class="form-group">
                <label for="member_role">Member Role</label>
                <select name="member_role" id="member_role" required>
                    <option value="">Select role</option>
                    <option value="student" {{ old('member_role') === 'student' ? 'selected' : '' }}>Student</option>
                    <option value="faculty" {{ old('member_role') === 'faculty' ? 'selected' : '' }}>Faculty</option>
                </select>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input
                        type="text"
                        name="last_name"
                        id="last_name"
                        value="{{ old('last_name') }}"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input
                        type="text"
                        name="first_name"
                        id="first_name"
                        value="{{ old('first_name') }}"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="middle_initial">Middle Initial</label>
                    <input
                        type="text"
                        name="middle_initial"
                        id="middle_initial"
                        value="{{ old('middle_initial') }}"
                        maxlength="3"
                        placeholder="Optional"
                    >
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-button">Save Member</button>
                <a href="{{ route('admin.members.index') }}" class="secondary-button">Cancel</a>
            </div>
        </form>
    </div>
@endsection