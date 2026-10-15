@extends('layouts.admin')

@section('title', 'Support Tickets Desk — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Support Tickets Helpdesk')

@section('content')
    {{-- Support Summary Counters --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Open (Awaiting Staff)</div>
                <div class="admin-stat-val text-warning">{{ number_format($summary['open'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">In Progress</div>
                <div class="admin-stat-val text-info">{{ number_format($summary['in_progress'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Answered</div>
                <div class="admin-stat-val text-success">{{ number_format($summary['answered'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Resolved / Closed</div>
                <div class="admin-stat-val text-secondary">{{ number_format($summary['closed'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.tickets.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search Ticket UID, Subject, Player..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="answered" {{ request('status') === 'answered' ? 'selected' : '' }}>Answered</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <select name="priority" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Priorities</option>
                    <option value="normal" {{ request('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
            <div class="col-12 col-sm-4 col-md-2">
                <select name="category" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Categories</option>
                    <option value="battle_issue" {{ request('category') === 'battle_issue' ? 'selected' : '' }}>Battle / Match</option>
                    <option value="deposit_wallet" {{ request('category') === 'deposit_wallet' ? 'selected' : '' }}>Points / Wallet</option>
                    <option value="account_profile" {{ request('category') === 'account_profile' ? 'selected' : '' }}>Account / Profile</option>
                    <option value="kyc_issue" {{ request('category') === 'kyc_issue' ? 'selected' : '' }}>KYC Verification</option>
                    <option value="technical_bug" {{ request('category') === 'technical_bug' ? 'selected' : '' }}>Technical Bug</option>
                    <option value="other" {{ request('category') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="col-12 col-sm-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.tickets.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Reset</a>
            </div>
        </form>

        {{-- Tickets Table --}}
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Ticket UID</th>
                        <th>Player</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                        <tr>
                            <td class="font-monospace text-info fw-bold small">
                                {{ $t->ticket_uid }}
                            </td>
                            <td>
                                <a href="{{ route('admin.ludo.users.show', $t->user_id) }}" class="text-white small fw-bold text-decoration-none d-block">
                                    {{ $t->user->name ?? 'Player #' . $t->user_id }}
                                </a>
                                <div class="text-secondary small">{{ $t->user->email ?? 'No email' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-light" style="font-size: 10px;">
                                    {{ ucwords(str_replace('_', ' ', $t->category)) }}
                                </span>
                            </td>
                            <td class="small" style="max-width: 220px;">
                                <div class="text-truncate text-light fw-semibold" title="{{ $t->subject }}">{{ $t->subject }}</div>
                                <div class="text-secondary small text-truncate" style="font-size: 10.5px;">{{ Str::limit($t->description, 45) }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $t->priority === 'urgent' ? 'bg-danger' : ($t->priority === 'high' ? 'bg-warning text-dark' : 'bg-secondary') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                    {{ $t->priority }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $t->status === 'open' ? 'bg-primary' : ($t->status === 'answered' ? 'bg-success' : ($t->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary')) }}" style="font-size: 9.5px; text-transform: uppercase;">
                                    {{ str_replace('_', ' ', $t->status) }}
                                </span>
                            </td>
                            <td class="text-secondary small">
                                {{ $t->created_at->format('d M, h:i A') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.ludo.tickets.show', $t->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No support tickets found matching the criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $tickets->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
