@extends('layouts.admin')

@section('title', 'Admin Dashboard — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Operational Overview')

@section('content')
    {{-- 10 Live Database Metrics Cards --}}
    <div class="row g-3 mb-4">
        {{-- Total Users --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.users.index') }}" class="text-decoration-none">
                <div class="admin-stat-card">
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

        {{-- Active Matches --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.matches.index', ['status' => 'running']) }}" class="text-decoration-none">
                <div class="admin-stat-card">
                    <div class="admin-stat-title">Active Running Matches</div>
                    <div class="admin-stat-val text-info">{{ number_format($metrics['active_matches']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        In-progress gameplay
                    </div>
                    <i class="fa-solid fa-gamepad admin-stat-icon text-info"></i>
                </div>
            </a>
        </div>

        {{-- Completed Matches --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.matches.index', ['status' => 'completed']) }}" class="text-decoration-none">
                <div class="admin-stat-card">
                    <div class="admin-stat-title">Completed Battles</div>
                    <div class="admin-stat-val text-success">{{ number_format($metrics['completed_matches']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Audited match results
                    </div>
                    <i class="fa-solid fa-trophy admin-stat-icon text-success"></i>
                </div>
            </a>
        </div>

        {{-- Tournaments --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.tournaments.index') }}" class="text-decoration-none">
                <div class="admin-stat-card">
                    <div class="admin-stat-title">Tournaments</div>
                    <div class="admin-stat-val text-warning">{{ number_format($metrics['tournaments']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Single-elimination brackets
                    </div>
                    <i class="fa-solid fa-award admin-stat-icon text-warning"></i>
                </div>
            </a>
        </div>

        {{-- Open Disputes --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.disputes.index', ['status' => 'open']) }}" class="text-decoration-none">
                <div class="admin-stat-card">
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

        {{-- Pending Overdue Results --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.matches.index', ['review_status' => 'under_review']) }}" class="text-decoration-none">
                <div class="admin-stat-card">
                    <div class="admin-stat-title">Pending Result Audits</div>
                    <div class="admin-stat-val text-warning">{{ number_format($metrics['pending_results']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Expired 15-min submissions
                    </div>
                    <i class="fa-solid fa-clock admin-stat-icon text-warning"></i>
                </div>
            </a>
        </div>

        {{-- Open Support Tickets --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.tickets.index', ['status' => 'open']) }}" class="text-decoration-none">
                <div class="admin-stat-card">
                    <div class="admin-stat-title">Support Tickets</div>
                    <div class="admin-stat-val text-info">{{ number_format($metrics['support_tickets']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Unresolved inquiries
                    </div>
                    <i class="fa-solid fa-headset admin-stat-icon text-info"></i>
                </div>
            </a>
        </div>

        {{-- Virtual Points Issued --}}
        <div class="col-6 col-md-4 col-xl-3">
            <a href="{{ route('admin.ludo.ledger.index', ['type' => 'deposit']) }}" class="text-decoration-none">
                <div class="admin-stat-card">
                    <div class="admin-stat-title">Virtual Points Issued</div>
                    <div class="admin-stat-val text-warning">🪙 {{ number_format($metrics['points_issued']) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">
                        Audited ledger deposits
                    </div>
                    <i class="fa-solid fa-coins admin-stat-icon text-warning"></i>
                </div>
            </a>
        </div>
    </div>

    {{-- Two Column Section: Live Urgent Disputes & Audit Activity Trail --}}
    <div class="row g-4">
        {{-- Urgent Disputes Column --}}
        <div class="col-12 col-xl-6">
            <div class="admin-card">
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
                                            <a href="{{ route('ludo.dispute.view', $disp->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10.5px;">
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
            <div class="admin-card">
                <div class="admin-card-header">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-clipboard-list text-info me-2"></i> Real-Time Audit Log Trail
                    </h6>
                    <span class="badge bg-secondary text-light" style="font-size: 10px;">Immutable Log</span>
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
@endsection
