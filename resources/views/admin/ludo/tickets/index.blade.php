@extends('layouts.admin')

@section('title', 'Support Tickets Desk — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Support Tickets Helpdesk')

@section('content')
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.tickets.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search Ticket UID, Subject, Player..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open (Awaiting Staff)</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="answered" {{ request('status') === 'answered' ? 'selected' : '' }}>Answered</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="category" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Categories</option>
                    <option value="match_issue" {{ request('category') === 'match_issue' ? 'selected' : '' }}>Match Issue</option>
                    <option value="deposit_issue" {{ request('category') === 'deposit_issue' ? 'selected' : '' }}>Points / Reload Issue</option>
                    <option value="kyc_verification" {{ request('category') === 'kyc_verification' ? 'selected' : '' }}>KYC Verification</option>
                    <option value="technical_bug" {{ request('category') === 'technical_bug' ? 'selected' : '' }}>Technical Bug</option>
                    <option value="account_query" {{ request('category') === 'account_query' ? 'selected' : '' }}>Account Query</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.tickets.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
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
                                <div class="text-white small fw-bold">{{ $t->user->name ?? 'Player #' . $t->user_id }}</div>
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
                                <a href="{{ route('ludo.ticket.view', $t->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                    Reply &rarr;
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
