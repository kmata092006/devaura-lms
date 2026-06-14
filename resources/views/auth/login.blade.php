<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | UBNHS Library Management System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ubnhs-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/ubnhs-logo.png') }}">

    <link rel="stylesheet" href="{{ asset('css/lms.css') }}?v={{ filemtime(public_path('css/lms.css')) }}">
</head>
<body class="login-body">
    <main class="login-page">
        <section class="login-brand-panel">
            <div class="school-logo-frame school-logo-login-large">
                <img src="{{ asset('images/ubnhs-logo.png') }}" alt="Upper Bicutan National High School logo">
            </div>

            <div>
                <p class="login-eyebrow">Upper Bicutan National High School</p>
                <h1>UBNHS Library Management System</h1>
                <p>
                    A secure platform for streamlined library circulation, 
                    attendance tracking, and administrative reporting.
                </p>
            </div>
        </section>

        <section class="login-card">
            <div class="login-card-header">
                <div class="school-logo-frame school-logo-login-small">
                    <img src="{{ asset('images/ubnhs-logo.png') }}" alt="Upper Bicutan National High School logo">
                </div>

                <div>
                    <h2>Sign in</h2>
                    <p>Use your assigned library personnel account.</p>
                </div>
            </div>

            {{-- Shows one-time login notices, such as kiosk session resets. --}}
            @if (session('info'))
                <div class="alert success">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Lists validation and authentication errors above the form. --}}
            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Authenticates personnel; the server resolves the role from the account. --}}
            <form method="POST" action="{{ route('login.submit') }}" class="login-form">
                @csrf

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        value="{{ old('username') }}"
                        placeholder="Enter username"
                        autocomplete="username"
                        maxlength="50"
                        required
                        autofocus
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
        </section>
    </main>
</body>
</html>
