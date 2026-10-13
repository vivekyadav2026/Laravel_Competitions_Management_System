<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Administration')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/fsaludoclub-icon.png') }}">

    {{-- Bootstrap 5 & FontAwesome --}}
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --admin-bg: #050811;
            --admin-surface: #0b1121;
            --admin-card: #10192e;
            --admin-border: rgba(212, 175, 55, 0.2);
            --admin-accent-blue: #00d2ff;
            --admin-accent-violet: #818cf8;
            --admin-accent-gold: #ffd700;
            --admin-text-main: #f8fafc;
            --admin-text-muted: #94a3b8;
        }

        body {
            background-color: var(--admin-bg);
            color: var(--admin-text-main);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
        }

        /* Sidebar Styling */
        .admin-sidebar {
            width: 268px;
            background: linear-gradient(180deg, #0b1224 0%, #050811 100%);
            border-right: 1px solid var(--admin-border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            min-height: 100vh;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.5);
        }

        .admin-sidebar-header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.2);
            background: rgba(212, 175, 55, 0.04);
        }

        .admin-sidebar-logo {
            width: 58px;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(212, 175, 55, 0.35));
        }

        .admin-sidebar-brand {
            font-family: 'Cinzel', 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.8px;
            color: #ffd700;
            margin: 0;
            line-height: 1.2;
        }

        .admin-sidebar-tag {
            font-size: 10px;
            color: var(--admin-accent-gold);
            font-weight: 600;
            text-transform: uppercase;
        }

        .admin-nav-list {
            list-style: none;
            padding: 15px 10px;
            margin: 0;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .admin-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--admin-text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .admin-nav-link i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .admin-nav-link:hover {
            color: #fff;
            background: rgba(56, 189, 248, 0.1);
        }

        .admin-nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, rgba(56, 189, 248, 0.2) 0%, rgba(129, 140, 248, 0.2) 100%);
            border-left: 3px solid var(--admin-accent-blue);
            font-weight: 600;
        }

        .admin-nav-badge {
            margin-left: auto;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 10px;
        }

        /* Main Content Wrapper */
        .admin-main {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: var(--admin-bg);
        }

        .admin-topbar {
            height: 65px;
            background: var(--admin-surface);
            border-bottom: 1px solid var(--admin-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            position: sticky;
            top: 0;
            z-index: 1010;
        }

        .admin-content {
            padding: 25px;
            flex-grow: 1;
        }

        /* Card System */
        .admin-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }

        .admin-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .admin-stat-card {
            background: linear-gradient(145deg, #131d36 0%, #0d162b 100%);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 12px;
            padding: 18px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .admin-stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--admin-accent-blue);
        }

        .admin-stat-title {
            font-size: 12px;
            color: var(--admin-text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .admin-stat-val {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }

        .admin-stat-icon {
            position: absolute;
            right: 18px;
            top: 18px;
            font-size: 26px;
            opacity: 0.2;
        }

        /* Tables */
        .admin-table {
            color: var(--admin-text-main);
            margin: 0;
        }

        .admin-table th {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--admin-text-muted);
            border-bottom: 1px solid var(--admin-border);
            padding: 12px 14px;
            background: transparent;
        }

        .admin-table td {
            font-size: 13px;
            padding: 12px 14px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: transparent;
        }

        .admin-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Badges */
        .badge-status {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
        }

        @media (max-width: 991px) {
            .admin-sidebar {
                position: fixed;
                left: -260px;
                transition: left 0.3s ease;
            }
            .admin-sidebar.show {
                left: 0;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    {{-- Sidebar Navigation --}}
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <img src="{{ asset('assets/images/fsaludoclub-logo.png') }}" alt="{{ config('ludo.brand_name', 'Fsaludoclub') }}" class="admin-sidebar-logo">
            <div>
                <h5 class="admin-sidebar-brand">{{ strtoupper(config('ludo.brand_name', 'FSALUDOCLUB')) }}</h5>
                <span class="admin-sidebar-tag"><i class="fa-solid fa-crown me-1"></i> Admin Console</span>
            </div>
        </div>

        <ul class="admin-nav-list">
            <li>
                <a href="{{ route('admin.ludo.dashboard') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'dashboard' ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line text-info"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.users.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'users' ? 'active' : '' }}">
                    <i class="fa-solid fa-users text-primary"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.tournaments.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'tournaments' ? 'active' : '' }}">
                    <i class="fa-solid fa-trophy text-warning"></i>
                    <span>Tournaments & Brackets</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.matches.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'matches' ? 'active' : '' }}">
                    <i class="fa-solid fa-gamepad text-success"></i>
                    <span>Matches & Results</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.disputes.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'disputes' ? 'active' : '' }}">
                    <i class="fa-solid fa-gavel text-danger"></i>
                    <span>Dispute Desk</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.kyc.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'kyc' ? 'active' : '' }}">
                    <i class="fa-solid fa-id-card text-warning"></i>
                    <span>KYC Verifications</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.tickets.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'tickets' ? 'active' : '' }}">
                    <i class="fa-solid fa-headset text-info"></i>
                    <span>Support Desk</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.ledger.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'ledger' ? 'active' : '' }}">
                    <i class="fa-solid fa-coins text-warning"></i>
                    <span>Virtual Points Ledger</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.referrals.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'referrals' ? 'active' : '' }}">
                    <i class="fa-solid fa-gift text-danger"></i>
                    <span>Referral Management</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.reports.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'reports' ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie text-success"></i>
                    <span>Reports & CSV Exports</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.audit.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'audit' ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-list text-info"></i>
                    <span>Audit Logs Trail</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.content.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'content' ? 'active' : '' }}">
                    <i class="fa-solid fa-newspaper text-info"></i>
                    <span>CMS & Rules</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.settings.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'settings' ? 'active' : '' }}">
                    <i class="fa-solid fa-sliders text-secondary"></i>
                    <span>System Settings</span>
                </a>
            </li>
        </ul>

        {{-- Return to Game Lobby Link --}}
        <div class="p-3 border-top border-secondary border-opacity-25">
            <a href="{{ route('ludo.home') }}" class="btn btn-outline-info btn-sm w-100 rounded-pill" target="_blank">
                <i class="fa-solid fa-external-link me-1"></i> Player Game Lobby
            </a>
        </div>
    </aside>

    {{-- Main App Area --}}
    <div class="admin-main">
        {{-- Topbar --}}
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary btn-sm d-lg-none" id="sidebarToggleBtn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h6 class="mb-0 fw-bold text-white">@yield('page_title', 'Overview')</h6>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-md-block">
                    <div class="text-white small fw-bold">{{ Auth::user()->name ?? 'Administrator' }}</div>
                    <div class="text-info" style="font-size: 11px;">{{ strtoupper(Auth::user()->role ?? 'ADMIN') }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="d-inline m-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Log Out">
                        <i class="fa-solid fa-power-off me-1"></i> Exit
                    </button>
                </form>
            </div>
        </header>

        {{-- Content Area --}}
        <main class="admin-content">
            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success bg-success bg-opacity-25 text-success border border-success border-opacity-50 small rounded-3 mb-3 py-2 px-3">
                    <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 small rounded-3 mb-3 py-2 px-3">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 small rounded-3 mb-3 py-2 px-3">
                    @foreach($errors->all() as $err)
                        <div>• {{ $err }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        document.getElementById('sidebarToggleBtn')?.addEventListener('click', function() {
            document.getElementById('adminSidebar')?.classList.toggle('show');
        });
    </script>
    @stack('scripts')
</body>
</html>
