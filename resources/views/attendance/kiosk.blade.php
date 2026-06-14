@php
    // Resolve named routes safely and fall back to direct URLs if needed.
    $routeUrl = function (string $routeName, string $fallbackUrl) {
        return \Illuminate\Support\Facades\Route::has($routeName)
            ? route($routeName)
            : url($fallbackUrl);
    };

    $memberTimeInAction = $routeUrl('attendance.member.time-in', '/attendance/member/time-in');
    $memberTimeOutAction = $routeUrl('attendance.member.time-out', '/attendance/member/time-out');

    $visitorTimeInAction = $routeUrl('attendance.visitor.time-in', '/attendance/visitor/time-in');
    $visitorTimeOutAction = $routeUrl('attendance.visitor.time-out', '/attendance/visitor/time-out');

    // Send kiosk administrators through the route that clears kiosk session data.
    $adminLoginUrl = $routeUrl('attendance.admin-login', '/attendance/admin-login');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Kiosk | UBNHS Library Management System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ubnhs-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/ubnhs-logo.png') }}">

    <link rel="stylesheet" href="{{ asset('css/lms.css') }}?v={{ time() }}">

    <style>
        :root {
            --ubnhs-navy: #08244a;
            --ubnhs-navy-soft: #0f376c;
            --ubnhs-blue: #2563eb;
            --ubnhs-blue-dark: #1d4ed8;
            --ubnhs-light: #f4f8ff;
            --ubnhs-border: #dbe7f5;
            --ubnhs-muted: #64748b;
            --ubnhs-text: #0f172a;
            --ubnhs-green: #16a34a;
            --ubnhs-orange: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.18), transparent 34%),
                linear-gradient(180deg, var(--ubnhs-navy) 0 48%, #f3f8ff 48% 100%);
            color: var(--ubnhs-text);
        }

        .ubnhs-kiosk-page {
            width: min(100% - 32px, 1100px);
            margin: 0 auto;
            padding: 26px 0 48px;
        }

        .ubnhs-kiosk-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            background: #ffffff;
            border: 1px solid rgba(219, 231, 245, 0.9);
            border-radius: 24px;
            padding: 18px 22px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.22);
        }

        .ubnhs-kiosk-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .ubnhs-kiosk-mark {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #ffffff;
            font-size: 14px;
            font-weight: 950;
            letter-spacing: -0.4px;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.25);
        }

        .ubnhs-kiosk-brand h1 {
            margin: 0;
            color: var(--ubnhs-text);
            font-size: 22px;
            font-weight: 950;
            letter-spacing: -0.4px;
        }

        .ubnhs-kiosk-brand p {
            margin: 4px 0 0;
            color: var(--ubnhs-muted);
            font-size: 14px;
            font-weight: 700;
        }

        .ubnhs-admin-login {
            min-height: 48px;
            padding: 0 22px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--ubnhs-blue);
            color: #ffffff;
            font-size: 15px;
            font-weight: 950;
            text-decoration: none;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.25);
        }

        .ubnhs-admin-login:hover {
            background: var(--ubnhs-blue-dark);
        }

        .ubnhs-kiosk-hero {
            margin: 24px 0 20px;
            color: #ffffff;
        }

        .ubnhs-hero-label {
            margin-bottom: 14px;
        }

        .ubnhs-hero-label span {
            display: block;
            margin-bottom: 4px;
            color: #93c5fd;
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .ubnhs-hero-label strong {
            display: block;
            color: #ffffff;
            font-size: clamp(26px, 3vw, 38px);
            font-weight: 950;
            line-height: 1.05;
            letter-spacing: -0.8px;
        }

        .ubnhs-date-time-panel {
            width: 100%;
            max-width: 800px;
            display: grid;
            grid-template-columns: minmax(240px, 1fr) 1px minmax(240px, 0.85fr);
            align-items: center;
            gap: 20px;
            padding: 18px 24px;
            border: 1px solid rgba(191, 219, 254, 0.32);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.09);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.16);
            backdrop-filter: blur(12px);
        }

        .ubnhs-date-caption {
            display: block;
            margin-bottom: 6px;
            color: #93c5fd;
            font-size: 12px;
            font-weight: 950;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .ubnhs-kiosk-date-line {
            margin: 0;
            color: #dbeafe;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: clamp(23px, 2.5vw, 34px);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.7px;
        }

        .ubnhs-time-divider {
            width: 1px;
            height: 74px;
            background: rgba(191, 219, 254, 0.35);
        }

        .ubnhs-kiosk-time-line {
            margin: 0;
            color: #ffffff;
            font-family: "Segoe UI Variable Display", "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: clamp(38px, 4vw, 60px);
            font-weight: 950;
            line-height: 1;
            letter-spacing: -1.8px;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
            text-shadow: 0 10px 26px rgba(0, 0, 0, 0.25);
        }

        .ubnhs-kiosk-card {
            background: #ffffff;
            border: 1px solid var(--ubnhs-border);
            border-radius: 28px;
            padding: 22px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
        }

        .ubnhs-alert {
            margin-bottom: 18px;
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 14px;
            font-weight: 850;
        }

        .ubnhs-alert.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .ubnhs-alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .ubnhs-alert p {
            margin: 0;
        }

        .ubnhs-kiosk-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 20px;
            padding: 8px;
            margin-bottom: 24px;
        }

        .ubnhs-kiosk-tab {
            min-height: 54px;
            border: none;
            border-radius: 14px;
            background: transparent;
            color: var(--ubnhs-blue-dark);
            font-size: 16px;
            font-weight: 950;
            cursor: pointer;
        }

        .ubnhs-kiosk-tab.active {
            background: var(--ubnhs-blue);
            color: #ffffff;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.22);
        }

        .ubnhs-kiosk-panel {
            display: none;
        }

        .ubnhs-kiosk-panel.active {
            display: block;
        }

        .ubnhs-panel-title {
            margin-bottom: 20px;
        }

        .ubnhs-panel-title h2 {
            margin: 0 0 6px;
            color: var(--ubnhs-text);
            font-size: 28px;
            font-weight: 950;
            letter-spacing: -0.6px;
        }

        .ubnhs-panel-title p {
            margin: 0;
            color: var(--ubnhs-muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .ubnhs-kiosk-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .ubnhs-kiosk-form-box {
            background: #f8fbff;
            border: 1px solid var(--ubnhs-border);
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05);
        }

        .ubnhs-kiosk-form-box h3 {
            margin: 0 0 18px;
            text-align: center;
            color: var(--ubnhs-text);
            font-size: 20px;
            font-weight: 950;
        }

        .ubnhs-form-group {
            margin-bottom: 14px;
        }

        .ubnhs-form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--ubnhs-text);
            font-size: 13px;
            font-weight: 950;
        }

        .ubnhs-form-group input {
            width: 100%;
            min-height: 50px;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 0 14px;
            background: #ffffff;
            color: var(--ubnhs-text);
            font-size: 15px;
            font-weight: 700;
        }

        .ubnhs-form-group input:focus {
            outline: none;
            border-color: var(--ubnhs-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
        }

        .ubnhs-name-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 110px;
            gap: 10px;
        }

        .ubnhs-kiosk-action {
            width: 100%;
            min-height: 54px;
            border: none;
            border-radius: 14px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 950;
            cursor: pointer;
        }

        .ubnhs-kiosk-action.time-in {
            background: var(--ubnhs-green);
            box-shadow: 0 14px 26px rgba(22, 163, 74, 0.20);
        }

        .ubnhs-kiosk-action.time-in:hover {
            background: #15803d;
        }

        .ubnhs-kiosk-action.time-out {
            background: var(--ubnhs-orange);
            box-shadow: 0 14px 26px rgba(245, 158, 11, 0.20);
        }

        .ubnhs-kiosk-action.time-out:hover {
            background: #d97706;
        }

        @media (max-width: 900px) {
            .ubnhs-kiosk-page {
                width: min(100% - 24px, 1100px);
                padding-top: 24px;
            }

            .ubnhs-kiosk-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .ubnhs-admin-login {
                width: 100%;
            }

            .ubnhs-kiosk-date-line {
                font-size: 28px;
            }

            .ubnhs-kiosk-time-line {
                font-size: 54px;
                letter-spacing: -1.4px;
            }

            .ubnhs-kiosk-form-grid,
            .ubnhs-name-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 560px) {
            .ubnhs-date-time-panel {
                padding: 18px;
                border-radius: 22px;
            }

            .ubnhs-kiosk-date-line {
                font-size: 22px;
            }

            .ubnhs-kiosk-time-line {
                font-size: 40px;
            }
        }
    </style>
</head>

<body>
    <main class="ubnhs-kiosk-page">
        <header class="ubnhs-kiosk-header">
            <div class="ubnhs-kiosk-brand">
                <div class="school-logo-frame school-logo-kiosk">
                    <img src="{{ asset('images/ubnhs-logo.png') }}" alt="Upper Bicutan National High School logo">
                </div>

                <div>
                    <h1>UBNHS Library Management System</h1>
                    <p>Attendance Kiosk</p>
                </div>
            </div>

            <a href="{{ $adminLoginUrl }}" class="ubnhs-admin-login">
                Admin Login
            </a>
        </header>

        <section class="ubnhs-kiosk-hero">
            <div class="ubnhs-hero-label">
                <span>Attendance Kiosk</span>
                <strong>Time In / Time Out</strong>
            </div>

            <div class="ubnhs-date-time-panel">
                <div>
                    <span class="ubnhs-date-caption">Today</span>
                    <p class="ubnhs-kiosk-date-line" id="kioskDateLine">
                        {{ now()->format('l, F j, Y') }}
                    </p>
                </div>

                <div class="ubnhs-time-divider"></div>

                <div>
                    <span class="ubnhs-date-caption">Current Time</span>
                    <h2 class="ubnhs-kiosk-time-line" id="kioskTimeLine">
                        {{ now()->format('h:i:s A') }}
                    </h2>
                </div>
            </div>
        </section>

        <section class="ubnhs-kiosk-card">
            {{-- Displays successful time-in or time-out messages. --}}
            @if (session('success'))
                <div class="ubnhs-alert success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Displays validation and attendance-related errors. --}}
            @if ($errors->any())
                <div class="ubnhs-alert error">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- Switches the kiosk between member and visitor attendance forms. --}}
            <div class="ubnhs-kiosk-tabs">
                <button type="button" class="ubnhs-kiosk-tab active" data-tab="member">
                    Student / Faculty
                </button>

                <button type="button" class="ubnhs-kiosk-tab" data-tab="visitor">
                    Visitor
                </button>
            </div>

            <div class="ubnhs-kiosk-panel active" id="memberPanel">
                <div class="ubnhs-panel-title">
                    <h2>Student / Faculty Attendance</h2>
                    <p>Enter your registered reference number to record time-in or time-out.</p>
                </div>

                <div class="ubnhs-kiosk-form-grid">
                    {{-- Records member time-in using a registered reference number. --}}
                    <form method="POST" action="{{ $memberTimeInAction }}" class="ubnhs-kiosk-form-box">
                        @csrf

                        <h3>Time In</h3>

                        <div class="ubnhs-form-group">
                            <label for="member_time_in_reference">Reference Number</label>
                            <input
                                type="text"
                                name="reference_number"
                                id="member_time_in_reference"
                                placeholder="Enter Reference Number"
                                autocomplete="off"
                                required
                            >
                        </div>

                        <button type="submit" class="ubnhs-kiosk-action time-in">
                            Time In
                        </button>
                    </form>

                    {{-- Records member time-out using a registered reference number. --}}
                    <form method="POST" action="{{ $memberTimeOutAction }}" class="ubnhs-kiosk-form-box">
                        @csrf

                        <h3>Time Out</h3>

                        <div class="ubnhs-form-group">
                            <label for="member_time_out_reference">Reference Number</label>
                            <input
                                type="text"
                                name="reference_number"
                                id="member_time_out_reference"
                                placeholder="Enter Reference Number"
                                autocomplete="off"
                                required
                            >
                        </div>

                        <button type="submit" class="ubnhs-kiosk-action time-out">
                            Time Out
                        </button>
                    </form>
                </div>
            </div>

            <div class="ubnhs-kiosk-panel" id="visitorPanel">
                <div class="ubnhs-panel-title">
                    <h2>Visitor Attendance</h2>
                    <p>Enter your name to record visitor time-in or time-out.</p>
                </div>

                <div class="ubnhs-kiosk-form-grid">
                    {{-- Creates a visitor record and records visitor time-in. --}}
                    <form method="POST" action="{{ $visitorTimeInAction }}" class="ubnhs-kiosk-form-box">
                        @csrf

                        <h3>Visitor Time In</h3>

                        <div class="ubnhs-form-group">
                            <label>Visitor Name</label>

                            <div class="ubnhs-name-grid">
                                <input
                                    type="text"
                                    name="last_name"
                                    placeholder="Last Name"
                                    pattern="^[a-zA-Z\s]+$"
                                    title="Only letters and spaces are allowed."
                                    oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                    autocomplete="off"
                                    required
                                >

                                <input
                                    type="text"
                                    name="first_name"
                                    placeholder="First Name"
                                    pattern="^[a-zA-Z\s]+$"
                                    title="Only letters and spaces are allowed."
                                    oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                    autocomplete="off"
                                    required
                                >

                                <input
                                    type="text"
                                    name="middle_initial"
                                    placeholder="M.I."
                                    maxlength="3"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <button type="submit" class="ubnhs-kiosk-action time-in">
                            Visitor Time In
                        </button>
                    </form>

                    {{-- Finds the active visitor log by name and records visitor time-out. --}}
                    <form method="POST" action="{{ $visitorTimeOutAction }}" class="ubnhs-kiosk-form-box">
                        @csrf

                        <h3>Visitor Time Out</h3>

                        <div class="ubnhs-form-group">
                            <label>Visitor Name</label>

                            <div class="ubnhs-name-grid">
                                <input
                                    type="text"
                                    name="last_name"
                                    placeholder="Last Name"
                                    pattern="^[a-zA-Z\s]+$"
                                    title="Only letters and spaces are allowed."
                                    oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                    autocomplete="off"
                                    required
                                >

                                <input
                                    type="text"
                                    name="first_name"
                                    placeholder="First Name"
                                    pattern="^[a-zA-Z\s]+$"
                                    title="Only letters and spaces are allowed."
                                    oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                                    autocomplete="off"
                                    required
                                >

                                <input
                                    type="text"
                                    name="middle_initial"
                                    placeholder="M.I."
                                    maxlength="3"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <button type="submit" class="ubnhs-kiosk-action time-out">
                            Visitor Time Out
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>
        const kioskDateLine = document.getElementById('kioskDateLine');
        const kioskTimeLine = document.getElementById('kioskTimeLine');

        /**
         * Updates the kiosk date and time display every second.
         */
        function updateKioskDateTime() {
            const now = new Date();

            const dateText = now.toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });

            const timeText = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
            });

            kioskDateLine.textContent = dateText;
            kioskTimeLine.textContent = timeText;
        }

        updateKioskDateTime();
        setInterval(updateKioskDateTime, 1000);

        const kioskTabs = document.querySelectorAll('.ubnhs-kiosk-tab');
        const memberPanel = document.getElementById('memberPanel');
        const visitorPanel = document.getElementById('visitorPanel');

        // Switch between member and visitor attendance panels.
        kioskTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                kioskTabs.forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');

                const selectedTab = tab.dataset.tab;

                if (selectedTab === 'visitor') {
                    memberPanel.classList.remove('active');
                    visitorPanel.classList.add('active');
                } else {
                    visitorPanel.classList.remove('active');
                    memberPanel.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>
