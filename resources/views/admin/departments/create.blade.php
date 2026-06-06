@extends('layouts.admin')

@section('title', 'Add Department')
@section('page-title', 'Add Department')

@section('content')
    <div class="page-actions department-header-card">
        <div>
            <h2>Add Department</h2>
            <p>Create a new department for library personnel assignment.</p>
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

    <form method="POST" action="{{ route('admin.departments.store') }}" class="department-form-card">
        @csrf

        <div class="department-form-group">
            <label for="department_name">Department Name</label>
            <input
                type="text"
                name="department_name"
                id="department_name"
                value="{{ old('department_name') }}"
                maxlength="100"
                placeholder="Enter Department Name"
                required
            >
        </div>

        <div class="department-form-actions">
            <button type="submit" class="department-primary-button">
                Save Department
            </button>

            <a href="{{ route('admin.departments.index') }}" class="department-reset-button">
                Cancel
            </a>
        </div>
    </form>
@endsection