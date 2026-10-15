@extends('layouts.admin')

@section('title', 'Reports & Analytics — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Platform Reports, Virtual-Points Analytics & CSV Exports')

@section('content')
    {{-- Date Filter & CSV Export Bar --}}
    <div class="admin-card mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-xl-7">
                <form action="{{ route('admin.ludo.reports.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-12 col-sm-6 col-md-3">
                        <select name="period" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="all" {{ ($period ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                            <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="7d" {{ ($period ?? '') === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="30d" {{ ($period ?? '') === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>
                    <div class="col-6 col-sm-3 col-md-3">
                        <input type="date" name="from" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $from ?? '' }}" title="From Date">
                    </div>
                    <div class="col-6 col-sm-3 col-md-3">
                        <input type="date" name="to" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $to ?? '' }}" title="To Date">
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                        <a href="{{ route('admin.ludo.reports.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Reset</a>
                    </div>
                </form>
            </div>
            <div class="col-12 col-xl-5 text-xl-end">
                <div class="d-flex flex-wrap gap-1 justify-content-xl-end">
                    <a href="{{ route('admin.ludo.reports.export', 'users') }}" class="btn btn-sm btn-outline-info rounded-pill px-2" style="font-size: 11px;">
                        <i class="fa-solid fa-file-csv me-1"></i> Users CSV
                    </a>
                    <a href="{{ route('admin.ludo.reports.export', 'matches') }}" class="btn btn-sm btn-outline-info rounded-pill px-2" style="font-size: 11px;">
                        <i class="fa-solid fa-file-csv me-1"></i> Matches CSV
                    </a>
                    <a href="{{ route('admin.ludo.reports.export', 'tournaments') }}" class="btn btn-sm btn-outline-info rounded-pill px-2" style="font-size: 11px;">
                        <i class="fa-solid fa-file-csv me-1"></i> Tournaments CSV
                    </a>
                    <a href="{{ route('admin.ludo.reports.export', 'ledger') }}" class="btn btn-sm btn-outline-warning rounded-pill px-2" style="font-size: 11px;">
                        <i class="fa-solid fa-file-csv me-1"></i> Ledger CSV
                    </a>
                    <a href="{{ route('admin.ludo.reports.export', 'referrals') }}" class="btn btn-sm btn-outline-info rounded-pill px-2" style="font-size: 11px;">
                        <i class="fa-solid fa-file-csv me-1"></i> Referrals CSV
                    </a>
                    <a href="{{ route('admin.ludo.reports.export', 'audit_logs') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2" style="font-size: 11px;">
                        <i class="fa-solid fa-file-csv me-1"></i> Audit CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Non-Cash Virtual Points Ledger Summary --}}
    <div class="admin-card mb-4">
        <div class="admin-card-header">
            <h6 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-coins text-warning me-2"></i> Virtual-Points Economy Summary (Non-Cash Gaming Points Only)
            </h6>
            <span class="badge bg-dark border border-warning text-warning" style="font-size: 10px;">Free Participation &bull; Non-Redeemable Virtual Points</span>
        </div>
        <div class="row g-3 text-center">
            <div class="col-6 col-md-2">
                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.25);">
                    <div class="text-secondary small">Circulating Balance</div>
                    <div class="text-warning fw-bold fs-5">🪙 {{ number_format($report['virtual_points']['circulating_balance'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.25);">
                    <div class="text-secondary small">Points Credited</div>
                    <div class="text-success fw-bold fs-5">+{{ number_format($report['virtual_points']['deposits_credited'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.25);">
                    <div class="text-secondary small">Entry Points Staked</div>
                    <div class="text-light fw-bold fs-5">🪙 {{ number_format($report['virtual_points']['entry_fees_staked'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.25);">
                    <div class="text-secondary small">Virtual Prizes Won</div>
                    <div class="text-info fw-bold fs-5">🪙 {{ number_format($report['virtual_points']['prizes_awarded'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.25);">
                    <div class="text-secondary small">Points Refunded</div>
                    <div class="text-primary fw-bold fs-5">🪙 {{ number_format($report['virtual_points']['refunds_returned'] ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.25);">
                    <div class="text-secondary small">Referral Bonuses</div>
                    <div class="text-warning fw-bold fs-5">🪙 {{ number_format($report['virtual_points']['referral_bonuses'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detailed Analytical Breakdowns --}}
    <div class="row g-4">
        {{-- Users & KYC Report --}}
        <div class="col-12 col-md-6">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-users text-primary me-1"></i> Player Registration & KYC Report
                </h6>
                <table class="table admin-table">
                    <tbody>
                        <tr><td>Total Registered Players</td><td class="text-end fw-bold text-white">{{ number_format($report['users']['total_registered'] ?? 0) }}</td></tr>
                        <tr><td>Active Accounts</td><td class="text-end fw-bold text-success">{{ number_format($report['users']['active'] ?? 0) }}</td></tr>
                        <tr><td>Suspended Accounts</td><td class="text-end fw-bold text-danger">{{ number_format($report['users']['suspended'] ?? 0) }}</td></tr>
                        <tr><td>KYC Verified Accounts</td><td class="text-end fw-bold text-info">{{ number_format($report['users']['kyc_verified'] ?? 0) }}</td></tr>
                        <tr><td>Pending KYC Reviews</td><td class="text-end fw-bold text-warning">{{ number_format($report['users']['kyc_pending'] ?? 0) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Matches & Result Review Workload --}}
        <div class="col-12 col-md-6">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-gamepad text-success me-1"></i> Battle Activity & Result Review Workload
                </h6>
                <table class="table admin-table">
                    <tbody>
                        <tr><td>Total Battles Created</td><td class="text-end fw-bold text-white">{{ number_format($report['matches']['total'] ?? 0) }}</td></tr>
                        <tr><td>Open / Running Battles</td><td class="text-end fw-bold text-info">{{ number_format($report['matches']['open'] ?? 0) }} Open / {{ number_format($report['matches']['running'] ?? 0) }} Running</td></tr>
                        <tr><td>Completed Matches</td><td class="text-end fw-bold text-success">{{ number_format($report['matches']['completed'] ?? 0) }}</td></tr>
                        <tr><td>Cancelled Matches</td><td class="text-end fw-bold text-danger">{{ number_format($report['matches']['cancelled'] ?? 0) }}</td></tr>
                        <tr><td>Pending Result Reviews / Disputed</td><td class="text-end fw-bold text-warning">{{ number_format($report['matches']['pending_reviews'] ?? 0) }} Review / {{ number_format($report['matches']['disputed_matches'] ?? 0) }} Disputed</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tournaments & Referrals --}}
        <div class="col-12 col-md-6">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-trophy text-warning me-1"></i> Tournament & Referral Activity
                </h6>
                <table class="table admin-table">
                    <tbody>
                        <tr><td>Total Tournaments</td><td class="text-end fw-bold text-white">{{ number_format($report['tournaments']['total'] ?? 0) }}</td></tr>
                        <tr><td>Published / Running Tournaments</td><td class="text-end fw-bold text-info">{{ number_format($report['tournaments']['published'] ?? 0) }} Open / {{ number_format($report['tournaments']['running'] ?? 0) }} Live</td></tr>
                        <tr><td>Completed / Cancelled Tournaments</td><td class="text-end fw-bold text-success">{{ number_format($report['tournaments']['completed'] ?? 0) }} Done / {{ number_format($report['tournaments']['cancelled'] ?? 0) }} Cancelled</td></tr>
                        <tr><td>Total Tournament Registrations</td><td class="text-end fw-bold text-warning">{{ number_format($report['tournaments']['total_participations'] ?? 0) }}</td></tr>
                        <tr><td>Eligible Referrals / Bonus Points</td><td class="text-end fw-bold text-info">{{ number_format($report['referrals']['eligible'] ?? 0) }} (🪙 {{ number_format($report['referrals']['virtual_points_rewarded'] ?? 0) }})</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Disputes & Support Tickets --}}
        <div class="col-12 col-md-6">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-headset text-info me-1"></i> Dispute Resolution & Support Statistics
                </h6>
                <table class="table admin-table">
                    <tbody>
                        <tr><td>Total Disputes Lodged</td><td class="text-end fw-bold text-white">{{ number_format($report['disputes']['total'] ?? 0) }}</td></tr>
                        <tr><td>Open vs Resolved Disputes</td><td class="text-end fw-bold"><span class="text-danger">{{ number_format($report['disputes']['open'] ?? 0) }} Open</span> / <span class="text-success">{{ number_format($report['disputes']['resolved'] ?? 0) }} Resolved</span></td></tr>
                        <tr><td>Total Support Tickets</td><td class="text-end fw-bold text-white">{{ number_format($report['tickets']['total'] ?? 0) }}</td></tr>
                        <tr><td>Open / In-Progress Tickets</td><td class="text-end fw-bold text-warning">{{ number_format($report['tickets']['open'] ?? 0) }} Open / {{ number_format($report['tickets']['in_progress'] ?? 0) }} Active</td></tr>
                        <tr><td>Answered / Closed Tickets</td><td class="text-end fw-bold text-success">{{ number_format($report['tickets']['answered'] ?? 0) }} Answered / {{ number_format($report['tickets']['closed'] ?? 0) }} Closed</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
