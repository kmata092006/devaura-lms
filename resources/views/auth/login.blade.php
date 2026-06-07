<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | UBNHS Library Management System</title>

    <link rel="stylesheet" href="{{ asset('css/lms.css') }}?v={{ filemtime(public_path('css/lms.css')) }}">
</head>
<body class="login-body">
    <main class="login-page">
        <section class="login-brand-panel">
            <div class="login-school-mark text-only">
                UBNHS
            </div>

            <div>
                <p class="login-eyebrow">Upper Bicutan National High School</p>
                <h1>UBNHS Library Management System</h1>
                <p>
                    A secure library administration portal for attendance monitoring,
                    book records, borrowing, returns, reports, and audit tracking.
                </p>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-header">
                <div class="login-card-text-logo">
                    UBNHS
                </div>

                <div>
                    <h2>Sign in</h2>
                    <p>Use your assigned library personnel account.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}" class="login-form">
                @csrf

                <div class="form-group">
                    <label for="role">Role</label>
                    <select name="role" id="role" required>
                        <option value="">Select role</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="staff" {{ old('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="student staff" {{ old('role') === 'student staff' ? 'selected' : '' }}>Student Staff</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username') }}"
                        placeholder="Enter username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="Enter password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="login-button">
                    Login
                </button>
            </form>

            <div class="login-help-box">
                <p><strong>Default Accounts</strong></p>
                <p>Admin: admin / devaura</p>
                <p>Staff: staff / devaura</p>
                <p>Student Staff: student staff / devaura</p>
            </div>
        </section>
    </main>
</body>
</html>