@extends('layouts.admin')

@section('title', 'Edit Department')
@section('page-title', 'Edit Department')

@section('content')
    <div class="page-actions department-header-card">
        <div>
            <h2>Edit Department</h2>
            <p>Update department information.</p>
        </div>

        <a href="{{ route('admin.departments.index') }}" class="department-reset-button">
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

    <form method="POST" action="{{ route('admin.departments.update', $department->department_id) }}" class="department-form-card">
        @csrf
        @method('PUT')

        <div class="department-form-group">
            <label for="department_name">Department Name</label>
            <input
                type="text"
                name="department_name"
                id="department_name"
                value="{{ old('department_name', $department->department_name) }}"
                maxlength="100"
                placeholder="Enter Department Name"
                required
            >
        </div>

        <div class="department-form-actions">
            <button type="submit" class="department-primary-button">
                Update Department
            </button>

            <a href="{{ route('admin.departments.index') }}" class="department-reset-button">
                Cancel
            </a>
        </div>
    </form>
@endsection