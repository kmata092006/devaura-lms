<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Kiosk | Library Management System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background:
                linear-gradient(rgba(5, 25, 48, 0.88), rgba(5, 25, 48, 0.94)),
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.35), transparent 35%),
                radial-gradient(circle at bottom left, rgba(34, 197, 94, 0.18), transparent 35%),
                #061b33;
            color: #ffffff;
        }

        .kiosk-page {
            width: min(1180px, calc(100% - 40px));
            margin: 0 auto;
            padding: 24px 0 36px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 14px 18px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin-bottom: 38px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            background: #ffffff;
            color: #061b33;
            font-weight: 900;
            padding: 10px 14px;
            border-radius: 999px;
        }

        .brand-text strong {
            display: block;
            font-size: 16px;
        }

        .admin-link {
            color: #ffffff;
            text-decoration: none;
            font-weight: 800;
            background: rgba(255, 255, 255, 0.14);
            padding: 10px 16px;
            border-radius: 999px;
        }

        .admin-link:hover {
            background: rgba(255, 255, 255, 0.24);
        }

        .hero {
            margin-bottom: 26px;
        }

        .date-text {
            font-family: Georgia, "Times New Roman", serif;
            color: rgba(255, 255, 255, 0.72);
            font-size: 30px;
            font-weight: 900;
            margin-bottom: 16px;
        }

        .hero h1 {
            margin: 0;
            font-size: clamp(52px, 8vw, 98px);
            line-height: 0.95;
            letter-spacing: -4px;
            text-shadow: 0 12px 24px rgba(0, 0, 0, 0.34);
        }

        .alert {
            margin-top: 22px;
            padding: 15px 18px;
            border-radius: 16px;
            font-weight: 800;
        }

        .alert p {
            margin: 0;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .kiosk-card {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 30px;
            padding: 20px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.3);
        }

        .tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 18px;
        }

        .tab-button {
            border: none;
            border-radius: 999px;
            padding: 15px 18px;
            background: rgba(255, 255, 255, 0.16);
            color: rgba(255, 255, 255, 0.84);
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
        }

        .tab-button.active {
            background: #ffffff;
            color: #0f172a;
        }

        .panel {
            display: none;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 24px;
        }

        .panel.active {
            display: block;
        }

        .panel-header {
            margin-bottom: 18px;
        }

        .panel-header h2 {
            margin: 0;
            font-size: 28px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-box {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 22px;
            padding: 18px;
            display: grid;
            gap: 12px;
        }

        .form-box h3 {
            margin: 0;
            font-size: 20px;
            text-align: center;
        }

        .form-box label {
            font-weight: 900;
            color: rgba(255, 255, 255, 0.9);
        }

        input {
            width: 100%;
            min-height: 56px;
            border: none;
            border-radius: 14px;
            padding: 0 16px;
            font-size: 16px;
            background: #ffffff;
            color: #111827;
            box-shadow: inset 0 -3px 0 rgba(15, 23, 42, 0.16);
        }

        input:focus {
            outline: 3px solid rgba(147, 197, 253, 0.55);
        }

        .name-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 90px;
            gap: 10px;
        }

        .kiosk-button {
            border: none;
            min-height: 56px;
            border-radius: 14px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
        }

        .time-in {
            background: #22c55e;
        }

        .time-in:hover {
            background: #16a34a;
        }

        .time-out {
            background: #f59e0b;
        }

        .time-out:hover {
            background: #d97706;
        }

        @media (max-width: 900px) {
            .action-grid,
            .name-grid {
                grid-template-columns: 1fr;
            }

            .hero h1 {
                letter-spacing: -2px;
            }
        }

        @media (max-width: 650px) {
            .kiosk-page {
                width: min(100% - 24px, 1180px);
            }

            .topbar {
                display: grid;
                border-radius: 22px;
            }

            .tabs {
                grid-template-columns: 1fr;
            }

            .date-text {
                font-size: 21px;
            }

            .hero h1 {
                font-size: 46px;
            }
        }
    </style>
</head>

<body>
    <main class="kiosk-page">
        <header class="topbar">
            <div class="brand">
                <div class="brand-logo">UBNHS</div>

                <div class="brand-text">
                    <strong>Library Management System</strong>
                </div>
            </div>

            <a href="{{ route('login') }}" class="admin-link">
                Admin Login
            </a>
        </header>

        <section class="hero">
            <div class="date-text" id="kioskDateText">
                Loading date...
            </div>

            <h1>TIME IN / TIME OUT</h1>

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
        </section>

        <section class="kiosk-card">
            <div class="tabs">
                <button type="button" class="tab-button active" data-tab="member">
                    Student / Faculty
                </button>

                <button type="button" class="tab-button" data-tab="visitor">
                    Visitor
                </button>
            </div>

            <div class="panel active" id="panel-member">
                <div class="panel-header">
                    <h2>Student / Faculty Attendance</h2>
                </div>

                <div class="action-grid">
                    <form method="POST" action="{{ route('attendance.member.time-in') }}" class="form-box">
                        @csrf

                        <h3>Time In</h3>

                        <label for="member_reference_time_in">Reference Number</label>
                        <input
                            type="text"
                            name="member_reference"
                            id="member_reference_time_in"
                            placeholder="Enter Reference Number"
                            maxlength="50"
                            autocomplete="off"
                            required
                        >

                        <button type="submit" class="kiosk-button time-in">
                            Time In
                        </button>
                    </form>

                    <form method="POST" action="{{ route('attendance.member.time-out') }}" class="form-box">
                        @csrf

                        <h3>Time Out</h3>

                        <label for="member_reference_time_out">Reference Number</label>
                        <input
                            type="text"
                            name="member_reference"
                            id="member_reference_time_out"
                            placeholder="Enter reference number"
                            maxlength="50"
                            autocomplete="off"
                            required
                        >

                        <button type="submit" class="kiosk-button time-out">
                            Time Out
                        </button>
                    </form>
                </div>
            </div>

            <div class="panel" id="panel-visitor">
                <div class="panel-header">
                    <h2>Visitor Attendance</h2>
                </div>

                <div class="action-grid">
                    <form method="POST" action="{{ route('attendance.visitor.time-in') }}" class="form-box">
                        @csrf

                        <h3>Visitor Time In</h3>

                        <label>Visitor Name</label>
                        <div class="name-grid">
                            <input
                                type="text"
                                name="last_name"
                                placeholder="Last name"
                                maxlength="50"
                                autocomplete="off"
                                required
                            >

                            <input
                                type="text"
                                name="first_name"
                                placeholder="First name"
                                maxlength="50"
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

                        <button type="submit" class="kiosk-button time-in">
                            Visitor Time In
                        </button>
                    </form>

                    <form method="POST" action="{{ route('attendance.visitor.time-out') }}" class="form-box">
                        @csrf

                        <h3>Visitor Time Out</h3>

                        <label>Visitor Name</label>
                        <div class="name-grid">
                            <input
                                type="text"
                                name="last_name"
                                placeholder="Last name"
                                maxlength="50"
                                autocomplete="off"
                                required
                            >

                            <input
                                type="text"
                                name="first_name"
                                placeholder="First name"
                                maxlength="50"
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

                        <button type="submit" class="kiosk-button time-out">
                            Visitor Time Out
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>
        function updateKioskClock() {
            const dateElement = document.getElementById('kioskDateText');

            if (!dateElement) {
                return;
            }

            const now = new Date();

            const formattedDate = now.toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric',
            });

            const formattedTime = now.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
            });

            dateElement.textContent = `${formattedDate} | ${formattedTime}`;
        }

        updateKioskClock();
        setInterval(updateKioskClock, 1000);

        document.querySelectorAll('[data-tab]').forEach((button) => {
            button.addEventListener('click', () => {
                const selectedTab = button.getAttribute('data-tab');

                document.querySelectorAll('[data-tab]').forEach((tab) => {
                    tab.classList.remove('active');
                });

                document.querySelectorAll('.panel').forEach((panel) => {
                    panel.classList.remove('active');
                });

                button.classList.add('active');

                const targetPanel = document.getElementById(`panel-${selectedTab}`);

                if (targetPanel) {
                    targetPanel.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>