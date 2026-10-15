<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Administration')</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/fsaludoclub-icon.png') }}">

    {{-- Bootstrap 5 & FontAwesome --}}
    <link rel="stylesheet" href="{{ versioned_asset('css/bootstrap.min.css') }}">
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
            box-sizing: border-box;
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
            width: calc(100% - 268px);
            max-width: calc(100% - 268px);
            background: var(--admin-bg);
            overflow-x: clip;
        }

        @media (max-width: 991px) {
            .admin-main {
                width: 100%;
                max-width: 100%;
                overflow-x: clip;
            }
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
            min-width: 0;
            max-width: 100%;
        }

        /* Card System */
        .admin-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
            min-width: 0;
            max-width: 100%;
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

        .admin-notification-pulse {
            animation: pulseNotificationBadge 2s infinite;
        }
        @keyframes pulseNotificationBadge {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1.08); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .admin-notification-item {
            transition: background 0.15s ease;
        }
        .admin-notification-item:hover {
            background: rgba(56, 189, 248, 0.1) !important;
        }

        /* Mobile Sidebar & Backdrop */
        .admin-sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(3px);
            z-index: 1040;
            transition: opacity 0.25s ease;
        }

        .admin-sidebar-backdrop.show {
            display: block;
        }

        body.admin-sidebar-open {
            overflow: hidden;
        }

        @media (max-width: 991px) {
            .admin-sidebar {
                position: fixed;
                left: -285px;
                top: 0;
                bottom: 0;
                height: 100vh;
                max-height: 100vh;
                overflow-y: auto;
                z-index: 1050;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: none;
            }
            .admin-sidebar.show {
                left: 0;
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.85);
            }
        }

        /* Mobile Topbar, Cards & Content */
        .admin-topbar-title {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        @media (min-width: 576px) {
            .admin-topbar-title {
                max-width: 340px;
                font-size: 16px;
            }
        }

        @media (min-width: 992px) {
            .admin-topbar-title {
                max-width: none;
            }
        }

        @media (max-width: 767px) {
            .row {
                --bs-gutter-x: 0.75rem;
            }
            .admin-topbar {
                height: 56px;
                padding: 0 12px;
            }
            .admin-content {
                padding: 14px 12px;
            }
            .admin-card {
                padding: 14px 12px;
                border-radius: 10px;
            }
            .admin-card-header {
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 12px;
                padding-bottom: 10px;
            }
            .admin-stat-card {
                padding: 12px 10px;
            }
            .admin-stat-val {
                font-size: 20px;
            }
            .admin-stat-title {
                font-size: 10.5px;
            }
            .admin-stat-icon {
                font-size: 18px;
                right: 10px;
                top: 10px;
                opacity: 0.15;
            }
        }

        @media (max-width: 575px) {
            .admin-notification-dropdown {
                width: calc(100vw - 20px) !important;
                max-width: calc(100vw - 20px) !important;
                position: fixed !important;
                top: 60px !important;
                left: 10px !important;
                right: 10px !important;
                transform: none !important;
            }
        }

        /* Global Responsive Pagination */
        .pagination {
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: 0;
            justify-content: center;
        }
        .page-link {
            font-size: 11.5px;
            padding: 4px 8px;
            background-color: #0b1224;
            border-color: rgba(255, 255, 255, 0.12);
            color: var(--admin-text-muted);
            border-radius: 6px !important;
        }
        .page-item.active .page-link {
            background-color: var(--admin-accent-blue);
            border-color: var(--admin-accent-blue);
            color: #040811;
            font-weight: 700;
        }
        .page-item.disabled .page-link {
            background-color: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.25);
        }

        /* Smooth Table Scrolling */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
        }

        /* Responsive Modal Sizing */
        .modal-dialog {
            margin: 0.5rem auto;
            max-width: calc(100% - 1rem);
        }
        @media (min-width: 576px) {
            .modal-dialog {
                max-width: 500px;
                margin: 1.75rem auto;
            }
        }
        @media (min-width: 992px) {
            .modal-dialog.modal-lg {
                max-width: 800px;
            }
            .modal-dialog.modal-xl {
                max-width: 1140px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

    {{-- Sidebar Mobile Backdrop Overlay --}}
    <div class="admin-sidebar-backdrop" id="sidebarBackdrop"></div>

    {{-- Sidebar Navigation --}}
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <img src="{{ asset('assets/images/fsaludoclub-logo.png') }}" alt="{{ config('ludo.brand_name', 'Fsaludoclub') }}" class="admin-sidebar-logo">
            <div class="flex-grow-1 min-w-0">
                <h5 class="admin-sidebar-brand text-truncate">{{ strtoupper(config('ludo.brand_name', 'FSALUDOCLUB')) }}</h5>
                <span class="admin-sidebar-tag"><i class="fa-solid fa-crown me-1"></i> Admin Console</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none ms-auto text-light border-0" id="sidebarCloseBtn" aria-label="Close Navigation">
                <i class="fa-solid fa-xmark fs-5"></i>
            </button>
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
                    <span class="badge bg-warning text-dark admin-nav-badge {{ ($pendingMatchReviewsCount ?? 0) > 0 ? '' : 'd-none' }}" id="sidebarBadgeMatches">{{ $pendingMatchReviewsCount ?? 0 }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.disputes.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'disputes' ? 'active' : '' }}">
                    <i class="fa-solid fa-gavel text-danger"></i>
                    <span>Dispute Desk</span>
                    <span class="badge bg-danger admin-nav-badge {{ ($pendingDisputesCount ?? 0) > 0 ? '' : 'd-none' }}" id="sidebarBadgeDisputes">{{ $pendingDisputesCount ?? 0 }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.kyc.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'kyc' ? 'active' : '' }}">
                    <i class="fa-solid fa-id-card text-warning"></i>
                    <span>KYC Verifications</span>
                    <span class="badge bg-warning text-dark admin-nav-badge {{ ($pendingKycCount ?? 0) > 0 ? '' : 'd-none' }}" id="sidebarBadgeKyc">{{ $pendingKycCount ?? 0 }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.tickets.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'tickets' ? 'active' : '' }}">
                    <i class="fa-solid fa-headset text-info"></i>
                    <span>Support Desk</span>
                    <span class="badge bg-info text-dark admin-nav-badge {{ ($pendingTicketsCount ?? 0) > 0 ? '' : 'd-none' }}" id="sidebarBadgeTickets">{{ $pendingTicketsCount ?? 0 }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.ledger.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'ledger' ? 'active' : '' }}">
                    <i class="fa-solid fa-coins text-warning"></i>
                    <span>Virtual Points Ledger</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.deposits.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'deposits' ? 'active' : '' }}">
                    <i class="fa-solid fa-qrcode text-success"></i>
                    <span>UPI QR Deposits</span>
                    <span class="badge bg-danger admin-nav-badge {{ ($pendingDepositsCount ?? 0) > 0 ? '' : 'd-none' }}" id="sidebarBadgeDeposits">{{ $pendingDepositsCount ?? 0 }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.ludo.withdrawals.index') }}" class="admin-nav-link {{ ($activeSection ?? '') === 'withdrawals' ? 'active' : '' }}">
                    <i class="fa-solid fa-money-bill-transfer text-warning"></i>
                    <span>Player Withdrawals</span>
                    <span class="badge bg-warning text-dark admin-nav-badge {{ ($pendingWithdrawalsCount ?? 0) > 0 ? '' : 'd-none' }}" id="sidebarBadgeWithdrawals">{{ $pendingWithdrawalsCount ?? 0 }}</span>
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
            <div class="d-flex align-items-center gap-2 gap-sm-3 min-w-0" style="flex: 1 1 auto; overflow: hidden;">
                <button class="btn btn-outline-secondary btn-sm d-lg-none flex-shrink-0" id="sidebarToggleBtn" aria-label="Open Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h6 class="mb-0 fw-bold text-white text-truncate admin-topbar-title">@yield('page_title', 'Overview')</h6>
            </div>

            <div class="d-flex align-items-center gap-2 gap-sm-3 flex-shrink-0 ms-2">
                {{-- Pending Activities Notification Bell Dropdown --}}
                <div class="dropdown me-1">
                    <button class="btn btn-outline-secondary position-relative rounded-pill px-2 px-sm-3 py-1 d-flex align-items-center gap-1 gap-sm-2" id="adminNotificationsMenuBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Pending Operational Activities">
                        <i class="fa-solid fa-bell text-warning"></i>
                        <span class="d-none d-sm-inline small text-light fw-semibold">Alerts</span>
                        @if(($totalPendingNotifications ?? 0) > 0)
                            <span class="badge rounded-pill bg-danger shadow-sm admin-notification-pulse" id="adminPendingBadge">
                                {{ $totalPendingNotifications }}
                            </span>
                        @else
                            <span class="badge rounded-pill bg-secondary bg-opacity-50 text-white" id="adminPendingBadge" style="font-size: 10px;">0</span>
                        @endif
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 shadow-lg border-warning border-opacity-25 admin-notification-dropdown" style="width: 380px; max-width: 92vw; background: #0c1427; border-radius: 14px; overflow: hidden;" aria-labelledby="adminNotificationsMenuBtn">
                        {{-- Dropdown Header --}}
                        <div class="p-3 border-bottom border-secondary border-opacity-25 d-flex justify-content-between align-items-center" style="background: rgba(212, 175, 55, 0.08);">
                            <div>
                                <h6 class="mb-0 fw-bold text-warning small text-uppercase" style="letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-bell me-1"></i> Pending Activities
                                </h6>
                                <span class="text-secondary" style="font-size: 11px;">Admin action required</span>
                            </div>
                            <span class="badge bg-danger rounded-pill px-2 py-1" style="font-size: 11px;" id="adminDropdownBadgeCount">
                                {{ $totalPendingNotifications ?? 0 }} Pending
                            </span>
                        </div>

                        {{-- Categorized Summary Chips --}}
                        <div class="p-2 border-bottom border-secondary border-opacity-25 d-flex gap-1 flex-wrap" style="background: rgba(0, 0, 0, 0.25);">
                            <a href="{{ route('admin.ludo.deposits.index') }}" class="badge bg-dark border border-success border-opacity-50 text-success text-decoration-none py-1 px-2" style="font-size: 10.5px;" id="adminChipDeposits">
                                Deposits: {{ $pendingDepositsCount ?? 0 }}
                            </a>
                            <a href="{{ route('admin.ludo.withdrawals.index') }}" class="badge bg-dark border border-warning border-opacity-50 text-warning text-decoration-none py-1 px-2" style="font-size: 10.5px;" id="adminChipPayouts">
                                Payouts: {{ $pendingWithdrawalsCount ?? 0 }}
                            </a>
                            <a href="{{ route('admin.ludo.kyc.index') }}" class="badge bg-dark border border-primary border-opacity-50 text-primary text-decoration-none py-1 px-2" style="font-size: 10.5px;" id="adminChipKyc">
                                KYC: {{ $pendingKycCount ?? 0 }}
                            </a>
                            <a href="{{ route('admin.ludo.disputes.index') }}" class="badge bg-dark border border-danger border-opacity-50 text-danger text-decoration-none py-1 px-2" style="font-size: 10.5px;" id="adminChipDisputes">
                                Disputes: {{ $pendingDisputesCount ?? 0 }}
                            </a>
                            <a href="{{ route('admin.ludo.tickets.index') }}" class="badge bg-dark border border-info border-opacity-50 text-info text-decoration-none py-1 px-2" style="font-size: 10.5px;" id="adminChipTickets">
                                Tickets: {{ $pendingTicketsCount ?? 0 }}
                            </a>
                        </div>

                        {{-- Notification Items List --}}
                        <div style="max-height: 330px; overflow-y: auto;" id="adminNotificationItemsList">
                            @forelse(($recentPendingActivities ?? []) as $act)
                                <a href="{{ $act['url'] }}" class="d-flex align-items-start gap-2 p-2 px-3 border-bottom border-secondary border-opacity-10 text-decoration-none text-light admin-notification-item">
                                    <div class="rounded-circle p-2 mt-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(255, 255, 255, 0.05); flex-shrink: 0;">
                                        <i class="{{ $act['icon'] }}"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="small text-white text-truncate">{{ $act['title'] }}</strong>
                                            <span class="badge {{ $act['badge_class'] ?? 'bg-secondary' }}" style="font-size: 9px;">{{ $act['badge'] ?? 'Notice' }}</span>
                                        </div>
                                        <div class="text-secondary small text-truncate" style="font-size: 11.5px;">{{ $act['description'] }}</div>
                                        <div class="text-muted" style="font-size: 10px;">{{ $act['created_at'] ? $act['created_at']->diffForHumans() : 'Recently' }}</div>
                                    </div>
                                </a>
                            @empty
                                <div class="p-4 text-center text-secondary">
                                    <i class="fa-solid fa-circle-check fs-2 text-success opacity-75 mb-2 d-block"></i>
                                    <strong class="text-white small d-block">All Caught Up!</strong>
                                    <span class="small" style="font-size: 11px;">No pending deposits, payouts, KYC, or disputes.</span>
                                </div>
                            @endforelse
                        </div>

                        {{-- Dropdown Footer --}}
                        <div class="p-2 text-center border-top border-secondary border-opacity-25" style="background: rgba(0, 0, 0, 0.35);">
                            <span class="text-secondary small" style="font-size: 10.5px;">
                                <i class="fa-solid fa-arrows-rotate me-1 text-warning"></i> Auto-refreshes every 20 seconds
                            </span>
                        </div>
                    </div>
                </div>

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
    <script src="{{ versioned_asset('js/bootstrap.bundle.min.js') }}"></script>
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
    <script>
        (function() {
            var sidebar = document.getElementById('adminSidebar');
            var backdrop = document.getElementById('sidebarBackdrop');
            var toggleBtn = document.getElementById('sidebarToggleBtn');
            var closeBtn = document.getElementById('sidebarCloseBtn');

            function openSidebar() {
                if (sidebar) sidebar.classList.add('show');
                if (backdrop) backdrop.classList.add('show');
                document.body.classList.add('admin-sidebar-open');
            }

            function closeSidebar() {
                if (sidebar) sidebar.classList.remove('show');
                if (backdrop) backdrop.classList.remove('show');
                document.body.classList.remove('admin-sidebar-open');
            }

            toggleBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (sidebar?.classList.contains('show')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            closeBtn?.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
            });

            backdrop?.addEventListener('click', function() {
                closeSidebar();
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar?.classList.contains('show')) {
                    closeSidebar();
                }
            });

            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992 && sidebar?.classList.contains('show')) {
                    closeSidebar();
                }
            });
        })();

        // Comprehensive Real-Time Live Polling Engine & Alert System for Admin Console
        (function() {
            let lastSeenDepositId = null;
            let lastSeenDisputeId = null;
            let lastSeenMatchReviewId = null;
            let isFirstPoll = true;
            let isPolling = false;

            // Native Web Audio Synthesizer Chime (Zero External Asset Dependencies)
            function playAdminChime(type) {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    if (ctx.state === 'suspended') {
                        ctx.resume();
                    }
                    const now = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);

                    if (type === 'deposit') {
                        // Upbeat 2-tone melodic chime (D5 -> A5)
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(587.33, now);
                        osc.frequency.exponentialRampToValueAtTime(880.00, now + 0.12);
                        gain.gain.setValueAtTime(0.2, now);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                        osc.start(now);
                        osc.stop(now + 0.45);
                    } else {
                        // Attention-grabbing alert chime (G5 -> C5)
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(783.99, now);
                        osc.frequency.exponentialRampToValueAtTime(523.25, now + 0.15);
                        gain.gain.setValueAtTime(0.25, now);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.4);
                        osc.start(now);
                        osc.stop(now + 0.4);
                    }
                } catch (e) {
                    // Audio context blocked by browser autoplay policy until user interacts
                }
            }

            // Floating Toast Notification
            function showAdminToast(title, body, url, badgeClass, icon) {
                const container = document.getElementById('adminLiveToastContainer');
                if (!container) return;

                const toastId = 'adm_toast_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
                const toastHtml = `
                    <div id="${toastId}" class="toast align-items-center text-white bg-dark border border-${badgeClass || 'warning'} shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 320px; border-radius: 12px; background: #0c1427 !important;">
                        <div class="toast-header bg-dark text-white border-bottom border-secondary border-opacity-25" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <i class="${icon || 'fa-solid fa-bell text-warning'} me-2"></i>
                            <strong class="me-auto small text-truncate">${title}</strong>
                            <span class="badge bg-${badgeClass || 'warning'} me-2" style="font-size: 9px;">REAL-TIME</span>
                            <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body p-2 px-3 d-flex justify-content-between align-items-center">
                            <div class="small text-light me-2">${body}</div>
                            ${url ? `<a href="${url}" class="btn btn-sm btn-outline-${badgeClass || 'info'} rounded-pill px-3 py-0 fw-bold flex-shrink-0" style="font-size: 11px;">Open &rarr;</a>` : ''}
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', toastHtml);
                const el = document.getElementById(toastId);
                if (el && window.bootstrap && window.bootstrap.Toast) {
                    const bsToast = new bootstrap.Toast(el, { delay: 8000 });
                    bsToast.show();
                    el.addEventListener('hidden.bs.toast', () => el.remove());
                }
            }

            // Expose globally for subviews to trigger
            window.showAdminToast = showAdminToast;
            window.playAdminChime = playAdminChime;

            async function pollAdminPendingActivities() {
                if (document.hidden || isPolling) return;
                isPolling = true;

                try {
                    const res = await fetch('{{ route('admin.ludo.notifications.live') }}?_t=' + Date.now(), {
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Cache-Control': 'no-cache, no-store, must-revalidate',
                            'Pragma': 'no-cache'
                        }
                    });

                    if (res.ok) {
                        const data = await res.json();
                        if (data && data.success) {
                            const counts = data.counts || {};
                            const total = data.total_pending || 0;

                            // 1. Update Topbar Notification Bell & Dropdown Title
                            const badge = document.getElementById('adminPendingBadge');
                            const dropBadge = document.getElementById('adminDropdownBadgeCount');
                            if (badge) {
                                badge.textContent = total;
                                if (total > 0) {
                                    badge.className = 'badge rounded-pill bg-danger shadow-sm admin-notification-pulse';
                                } else {
                                    badge.className = 'badge rounded-pill bg-secondary bg-opacity-50 text-white';
                                }
                            }
                            if (dropBadge) {
                                dropBadge.textContent = total + ' Pending';
                            }

                            // 2. Update Category Summary Chips
                            const chipDep = document.getElementById('adminChipDeposits');
                            if (chipDep) chipDep.textContent = 'Deposits: ' + (counts.deposits ?? 0);
                            const chipPay = document.getElementById('adminChipPayouts');
                            if (chipPay) chipPay.textContent = 'Payouts: ' + (counts.withdrawals ?? 0);
                            const chipKyc = document.getElementById('adminChipKyc');
                            if (chipKyc) chipKyc.textContent = 'KYC: ' + (counts.kyc ?? 0);
                            const chipDisp = document.getElementById('adminChipDisputes');
                            if (chipDisp) chipDisp.textContent = 'Disputes: ' + (counts.disputes ?? 0);
                            const chipTkt = document.getElementById('adminChipTickets');
                            if (chipTkt) chipTkt.textContent = 'Tickets: ' + (counts.tickets ?? 0);

                            // 3. Update Sidebar Nav Badges
                            const updateBadge = (id, count) => {
                                const el = document.getElementById(id);
                                if (el) {
                                    el.textContent = count;
                                    if (count > 0) {
                                        el.classList.remove('d-none');
                                    } else {
                                        el.classList.add('d-none');
                                    }
                                }
                            };
                            updateBadge('sidebarBadgeDeposits', counts.deposits ?? 0);
                            updateBadge('sidebarBadgeWithdrawals', counts.withdrawals ?? 0);
                            updateBadge('sidebarBadgeKyc', counts.kyc ?? 0);
                            updateBadge('sidebarBadgeDisputes', counts.disputes ?? 0);
                            updateBadge('sidebarBadgeTickets', counts.tickets ?? 0);
                            updateBadge('sidebarBadgeMatches', counts.match_reviews ?? 0);

                            // 4. Check for New Deposit Alert
                            if (data.latest && data.latest.deposit) {
                                const curDepId = data.latest.deposit.id;
                                if (isFirstPoll) {
                                    lastSeenDepositId = curDepId;
                                } else if (lastSeenDepositId !== null && curDepId > lastSeenDepositId) {
                                    lastSeenDepositId = curDepId;
                                    playAdminChime('deposit');
                                    showAdminToast(
                                        'New Deposit Request!',
                                        `₹${data.latest.deposit.amount} from ${data.latest.deposit.user_name} (UTR: <span class="font-monospace text-warning">${data.latest.deposit.utr_number}</span>)`,
                                        '{{ route('admin.ludo.deposits.index') }}',
                                        'success',
                                        'fa-solid fa-qrcode text-success'
                                    );
                                    window.dispatchEvent(new CustomEvent('admin:deposit-alert', { detail: data.latest.deposit }));
                                }
                            }

                            // 5. Check for New Dispute Alert
                            if (data.latest && data.latest.dispute) {
                                const curDispId = data.latest.dispute.id;
                                if (isFirstPoll) {
                                    lastSeenDisputeId = curDispId;
                                } else if (lastSeenDisputeId !== null && curDispId > lastSeenDisputeId) {
                                    lastSeenDisputeId = curDispId;
                                    playAdminChime('dispute');
                                    showAdminToast(
                                        'New Dispute Opened!',
                                        `Match ${data.latest.dispute.battle_uid || data.latest.dispute.dispute_uid} flagged for review.`,
                                        '{{ route('admin.ludo.disputes.index') }}',
                                        'danger',
                                        'fa-solid fa-gavel text-danger'
                                    );
                                    window.dispatchEvent(new CustomEvent('admin:dispute-alert', { detail: data.latest.dispute }));
                                }
                            }

                            // 6. Check for Match Review Alert
                            if (data.latest && data.latest.match_review) {
                                const curMatchKey = data.latest.match_review.id + '_' + (data.latest.match_review.review_status || 'none') + '_' + (data.latest.match_review.refund_status || 'none');
                                if (isFirstPoll) {
                                    lastSeenMatchReviewId = curMatchKey;
                                } else if (lastSeenMatchReviewId !== null && curMatchKey !== lastSeenMatchReviewId) {
                                    lastSeenMatchReviewId = curMatchKey;
                                    playAdminChime('dispute');
                                    showAdminToast(
                                        'Match Under Review / Dispute!',
                                        `Match ${data.latest.match_review.battle_uid} requires administrative verification.`,
                                        '{{ route('admin.ludo.matches.index') }}',
                                        'warning',
                                        'fa-solid fa-scale-balanced text-warning'
                                    );
                                    window.dispatchEvent(new CustomEvent('admin:matches-alert', { detail: data.latest.match_review }));
                                }
                            }

                            isFirstPoll = false;
                        }
                    }
                } catch (err) {
                    console.debug('Admin live poll error:', err);
                } finally {
                    isPolling = false;
                }
            }

            // Initial poll & 3-second recurring interval
            pollAdminPendingActivities();
            const pollTimer = setInterval(pollAdminPendingActivities, 3000);

            // Responsive trigger when switching tabs back to the admin console
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    pollAdminPendingActivities();
                }
            });
        })();
    </script>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="adminLiveToastContainer" style="z-index: 1090;"></div>
    @stack('scripts')
</body>
</html>
