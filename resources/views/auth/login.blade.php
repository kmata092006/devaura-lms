<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Library Management System</title>
    <link rel="stylesheet" href="{{ asset('css/lms.css') }}">
</head>
<body class="login-body">
    <main class="login-wrapper">
        <section class="login-card">
            <div class="login-header">
                <h1>Library Management System</h1>
                <p>Sign in using your assigned library personnel account.</p>
            </div>

            @if (session('success'))
                <div class="alert success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.process') }}" class="login-form">
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
                        required
                    >
                </div>

                <button type="submit" class="primary-button">Login</button>
            </form>

            <div class="demo-credentials">
                <p><strong>Admin:</strong> admin / devaura</p>
                <p><strong>Staff:</strong> staff / devaura</p>
                <p><strong>Student Staff:</strong> student staff / devaura</p>
            </div>
        </section>
    </main>
</body>
</html>