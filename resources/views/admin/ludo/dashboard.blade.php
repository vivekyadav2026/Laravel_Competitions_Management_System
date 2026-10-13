@extends('layouts.admin')

@section('title', 'Admin Dashboard — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Operational Overview')

@section('content')
    {{-- Date Range Filter Bar --}}
    <div class="admin-card mb-4 py-3">
        <form action="{{ route('admin.ludo.dashboard') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <div class="text-white fw-bold small">
                    <i class="fa-solid fa-calendar-days text-warning me-1"></i> Telemetry Date Range
                </div>
                <span class="text-secondary" style="font-size: 11px;">Filter dashboard metrics by period</span>
            </div>
            <div class="col-6 col-md-2">
                <select name="period" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="all" {{ ($period ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ ($period ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="7d" {{ ($period ?? '') === '7d' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="30d" {{ ($period ?? '') === '30d' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="custom" {{ ($period ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="from" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $from ?? '' }}" placeholder="From">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="to" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $to ?? '' }}" placeholder="To">
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold flex-grow-1">Apply Filter</button>
                <a href="{{ route('admin.ludo.dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
            </div>
        </form>
    </div>

    {{-- 12 Live Database Metrics Cards --}}
    <div class="row g-3 mb-4">
        {{-- 1. Total Users (Active vs Suspended) --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.users.index') }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Registered Players</div>
                    <div class="admin-stat-val text-white">{{ number_format($metrics['registered_users']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        <span class="text-success fw-bold">{{ $metrics['active_users'] }} Active</span> &bull;
                        <span class="text-danger fw-bold">{{ $metrics['suspended_users'] }} Suspended</span>
                    </div>
                    <i class="fa-solid fa-users admin-stat-icon text-primary"></i>
                </div>
            </a>
        </div>

        {{-- 2. Verified vs Unverified KYC --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.kyc.index') }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">KYC Verification</div>
                    <div class="admin-stat-val text-info">{{ number_format($metrics['verified_users']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        <span class="text-success fw-bold">{{ $metrics['verified_users'] }} Verified</span> &bull;
                        <span class="text-warning fw-bold">{{ $metrics['pending_kyc'] }} Pending</span> &bull;
                        <span>{{ $metrics['unverified_users'] }} Unverified</span>
                    </div>
                    <i class="fa-solid fa-id-card admin-stat-icon text-info"></i>
                </div>
            </a>
        </div>

        {{-- 3. Open & Running Battles --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.matches.index', ['status' => 'running']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Active & Open Battles</div>
                    <div class="admin-stat-val text-info">{{ number_format($metrics['active_matches']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        <span class="text-info fw-bold">{{ $metrics['active_matches'] }} Running</span> &bull;
                        <span class="text-warning fw-bold">{{ $metrics['open_battles'] }} Open Lobby</span>
                    </div>
                    <i class="fa-solid fa-gamepad admin-stat-icon text-info"></i>
                </div>
            </a>
        </div>

        {{-- 4. Completed & Cancelled Matches --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.matches.index', ['status' => 'completed']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Completed Battles</div>
                    <div class="admin-stat-val text-success">{{ number_format($metrics['completed_matches']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        <span class="text-success fw-bold">{{ $metrics['completed_matches'] }} Settled</span> &bull;
                        <span class="text-danger fw-bold">{{ $metrics['cancelled_matches'] }} Cancelled</span>
                    </div>
                    <i class="fa-solid fa-trophy admin-stat-icon text-success"></i>
                </div>
            </a>
        </div>

        {{-- 5. Tournaments Breakdown --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.tournaments.index') }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Tournaments</div>
                    <div class="admin-stat-val text-warning">{{ number_format($metrics['tournaments']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        <span class="text-info">{{ $metrics['tournaments_upcoming'] }} Open</span> &bull;
                        <span class="text-primary">{{ $metrics['tournaments_running'] }} Live</span> &bull;
                        <span class="text-success">{{ $metrics['tournaments_completed'] }} Done</span> &bull;
                        <span class="text-danger">{{ $metrics['tournaments_cancelled'] }} Canc.</span>
                    </div>
                    <i class="fa-solid fa-award admin-stat-icon text-warning"></i>
                </div>
            </a>
        </div>

        {{-- 6. Pending Result Reviews --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.matches.index', ['review_status' => 'under_review']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Pending Result Audits</div>
                    <div class="admin-stat-val text-warning">{{ number_format($metrics['pending_results']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Expired 15-min submissions
                    </div>
                    <i class="fa-solid fa-clock admin-stat-icon text-warning"></i>
                </div>
            </a>
        </div>

        {{-- 7. Open Disputes --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.disputes.index', ['status' => 'open']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Open Disputes</div>
                    <div class="admin-stat-val {{ $metrics['open_disputes'] > 0 ? 'text-danger' : 'text-secondary' }}">
                        {{ number_format($metrics['open_disputes']) }}
                    </div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Awaiting evidence review
                    </div>
                    <i class="fa-solid fa-gavel admin-stat-icon text-danger"></i>
                </div>
            </a>
        </div>

        {{-- 8. Support Tickets --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.tickets.index', ['status' => 'open']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Support Tickets</div>
                    <div class="admin-stat-val text-info">{{ number_format($metrics['support_tickets']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Unresolved player inquiries
                    </div>
                    <i class="fa-solid fa-headset admin-stat-icon text-info"></i>
                </div>
            </a>
        </div>

        {{-- 9. Referral Activity --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.referrals.index') }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Referral Invites</div>
                    <div class="admin-stat-val text-info">{{ number_format($metrics['total_referrals']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        <span class="text-success fw-bold">{{ $metrics['eligible_referrals'] }} Eligible</span> &bull;
                        <span class="text-warning fw-bold">🪙 {{ number_format($metrics['referral_points_awarded']) }}</span>
                    </div>
                    <i class="fa-solid fa-gift admin-stat-icon text-danger"></i>
                </div>
            </a>
        </div>

        {{-- 10. Circulating Player Virtual Balances --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.ledger.index') }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Player Wallet Balances</div>
                    <div class="admin-stat-val text-warning">🪙 {{ number_format($metrics['circulating_points']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Total non-cash virtual points held
                    </div>
                    <i class="fa-solid fa-wallet admin-stat-icon text-warning"></i>
                </div>
            </a>
        </div>

        {{-- 11. Virtual Points Issued --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.ledger.index', ['type' => 'deposit']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Virtual Points Issued</div>
                    <div class="admin-stat-val text-warning">🪙 {{ number_format($metrics['points_issued']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Audited ledger deposits
                    </div>
                    <i class="fa-solid fa-coins admin-stat-icon text-warning"></i>
                </div>
            </a>
        </div>

        {{-- 12. Virtual Prizes & Refunds --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.reports.index') }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Virtual Prizes Awarded</div>
                    <div class="admin-stat-val text-success">🪙 {{ number_format($metrics['points_prizes_paid']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Refunds: <span class="text-info fw-bold">🪙 {{ number_format($metrics['points_refunded']) }}</span>
                    </div>
                    <i class="fa-solid fa-chart-pie admin-stat-icon text-success"></i>
                </div>
            </a>
        </div>
    </div>

    {{-- Two Column Section: Live Urgent Disputes & Audit Activity Trail --}}
    <div class="row g-4 mb-4">
        {{-- Urgent Disputes Column --}}
        <div class="col-12 col-xl-6">
            <div class="admin-card h-100">
                <div class="admin-card-header">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Actionable Disputes
                    </h6>
                    <a href="{{ route('admin.ludo.disputes.index') }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                        View All
                    </a>
                </div>

                @if($recentDisputes->count() > 0)
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Dispute UID</th>
                                    <th>Battle</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentDisputes as $disp)
                                    <tr>
                                        <td class="font-monospace text-info">{{ $disp->dispute_uid }}</td>
                                        <td class="font-monospace text-light">{{ $disp->battle->battle_uid ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-dark text-warning border border-secondary" style="font-size: 10px;">
                                                {{ ucwords(str_replace('_', ' ', $disp->reason_category)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $disp->status === 'open' ? 'bg-danger' : ($disp->status === 'resolved' ? 'bg-success' : 'bg-warning text-dark') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                                {{ str_replace('_', ' ', $disp->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.ludo.disputes.show', $disp->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10.5px;">
                                                Review &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-secondary small">
                        <i class="fa-solid fa-circle-check fs-2 text-success opacity-50 mb-2 d-block"></i>
                        No active disputes found. Match play is calm.
                    </div>
                @endif
            </div>
        </div>

        {{-- Administrative Audit Log Activity Column --}}
        <div class="col-12 col-xl-6">
            <div class="admin-card h-100">
                <div class="admin-card-header">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-clipboard-list text-info me-2"></i> Real-Time Audit Log Trail
                    </h6>
                    <a href="{{ route('admin.ludo.audit.index') }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                        Full Audit Log &rarr;
                    </a>
                </div>

                @if($recentAuditLogs->count() > 0)
                    <div class="d-flex flex-column gap-2">
                        @foreach($recentAuditLogs as $log)
                            <div class="p-2 rounded-2" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.05);">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="badge bg-dark text-info border border-secondary" style="font-size: 9px; text-transform: uppercase;">
                                        {{ str_replace('_', ' ', $log->action_type) }}
                                    </span>
                                    <span class="text-secondary small" style="font-size: 10.5px;">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-light small" style="font-size: 12px;">{{ $log->description }}</div>
                                <div class="text-secondary" style="font-size: 10px; margin-top: 2px;">
                                    Executed by: <strong class="text-white">{{ $log->admin->name ?? 'Admin #' . $log->admin_id }}</strong>
                                    @if($log->ip_address) &bull; IP: {{ $log->ip_address }} @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-secondary small">
                        No administrative actions recorded yet.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Players & Recent Matches Row --}}
    <div class="row g-4">
        {{-- Recent Registered Users --}}
        <div class="col-12 col-xl-6">
            <div class="admin-card h-100">
                <div class="admin-card-header">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-user-plus text-primary me-2"></i> Recent Player Registrations
                    </h6>
                    <a href="{{ route('admin.ludo.users.index') }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                        All Players
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>Name</th>
                                <th>Points</th>
                                <th>KYC</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentUsers ?? [] as $ru)
                                <tr>
                                    <td class="font-monospace text-info small">{{ $ru->player_uid ?? 'LUDO' . str_pad($ru->id, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td class="text-white small fw-bold">{{ $ru->name }}</td>
                                    <td class="text-warning small fw-bold">🪙 {{ number_format($ru->coins) }}</td>
                                    <td>
                                        <span class="badge {{ $ru->kyc_status === 'verified' ? 'bg-success' : 'bg-secondary' }}" style="font-size: 9px; text-transform: uppercase;">
                                            {{ $ru->kyc_status ?? 'unverified' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.ludo.users.show', $ru->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10.5px;">
                                            Dossier
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-3 small">No registered users yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Battles --}}
        <div class="col-12 col-xl-6">
            <div class="admin-card h-100">
                <div class="admin-card-header">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-gamepad text-success me-2"></i> Recent Match Activity
                    </h6>
                    <a href="{{ route('admin.ludo.matches.index') }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                        All Matches
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>Battle Ref</th>
                                <th>Players</th>
                                <th>Stakes</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMatches ?? [] as $rm)
                                <tr>
                                    <td class="font-monospace text-info small">{{ $rm->battle_uid }}</td>
                                    <td class="small">
                                        <span class="text-white fw-bold">{{ $rm->creator->name ?? 'P1' }}</span>
                                        <span class="text-secondary">vs {{ $rm->joiner->name ?? 'Open' }}</span>
                                    </td>
                                    <td class="small text-warning">🪙 {{ $rm->entry_fee }} / {{ $rm->prize }}</td>
                                    <td>
                                        <span class="badge {{ $rm->status === 'completed' ? 'bg-success' : ($rm->status === 'running' ? 'bg-primary' : 'bg-secondary') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                            {{ $rm->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.ludo.matches.show', $rm->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10.5px;">
                                            Audit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-secondary py-3 small">No recent matches recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
