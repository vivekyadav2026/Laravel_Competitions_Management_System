@extends('layouts.admin')

@section('title', 'Dispute Management — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Dispute Resolution Desk')

@section('content')
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.disputes.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search by Dispute UID or Battle UID..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open (Awaiting Inspection)</option>
                    <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="more_evidence_requested" {{ request('status') === 'more_evidence_requested' ? 'selected' : '' }}>More Evidence Requested</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-6 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter Disputes</button>
                <a href="{{ route('admin.ludo.disputes.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
            </div>
        </form>

        {{-- Disputes Table --}}
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Dispute UID</th>
                        <th>Battle Reference</th>
                        <th>Complainant vs Opponent</th>
                        <th>Reason Category</th>
                        <th>Status</th>
                        <th>Resolution Rationale</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disputes as $d)
                        <tr>
                            <td class="font-monospace text-danger fw-bold small">
                                {{ $d->dispute_uid }}
                                <div class="text-secondary" style="font-size: 10px;">{{ $d->created_at->format('d M, h:i A') }}</div>
                            </td>
                            <td class="font-monospace text-info small">
                                {{ $d->battle->battle_uid ?? 'N/A' }}
                                <div class="text-secondary" style="font-size: 10px;">🪙 {{ $d->battle->prize ?? 0 }} pts</div>
                            </td>
                            <td>
                                <div class="text-white small fw-bold">{{ $d->openedBy->name ?? 'Player 1' }}</div>
                                <div class="text-secondary small">vs {{ $d->againstUser->name ?? 'Player 2' }}</div>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-warning" style="font-size: 10px;">
                                    {{ ucwords(str_replace('_', ' ', $d->reason_category)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $d->status === 'open' ? 'bg-danger' : ($d->status === 'resolved' ? 'bg-success' : ($d->status === 'more_evidence_requested' ? 'bg-warning text-dark' : 'bg-primary')) }}">
                                    {{ strtoupper(str_replace('_', ' ', $d->status)) }}
                                </span>
                            </td>
                            <td class="small" style="max-width: 250px;">
                                @if($d->admin_decision_reason)
                                    <div class="text-truncate text-light" title="{{ $d->admin_decision_reason }}">{{ $d->admin_decision_reason }}</div>
                                    <span class="text-secondary" style="font-size: 10px;">By {{ $d->resolver->name ?? 'Admin' }}</span>
                                @else
                                    <span class="text-secondary">Pending verdict</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.ludo.disputes.show', $d->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                    Dossier &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No dispute records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $disputes->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
