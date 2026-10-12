@extends('layouts.admin')

@section('title', 'Matches & Results — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Match Management & Auditing')

@section('content')
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.matches.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search by Battle UID, room code, player..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="review_status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Reviews</option>
                    <option value="under_review" {{ request('review_status') === 'under_review' ? 'selected' : '' }}>Under Review (Overdue/Late)</option>
                    <option value="disputed" {{ request('review_status') === 'disputed' ? 'selected' : '' }}>Disputed</option>
                    <option value="auto_approved" {{ request('review_status') === 'auto_approved' ? 'selected' : '' }}>Auto Approved</option>
                    <option value="verified" {{ request('review_status') === 'verified' ? 'selected' : '' }}>Verified</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.matches.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
            </div>
        </form>

        {{-- Matches Table --}}
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Battle Ref</th>
                        <th>Participants</th>
                        <th>Room Code</th>
                        <th>Entry / Prize</th>
                        <th>Match Status</th>
                        <th>Review Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matches as $m)
                        <tr>
                            <td class="font-monospace text-info small">
                                <div>{{ $m->battle_uid }}</div>
                                <span class="text-secondary" style="font-size: 10px;">{{ $m->created_at->format('d M, h:i A') }}</span>
                            </td>
                            <td>
                                <div class="text-white small fw-bold">{{ $m->creator->name ?? 'P1' }}</div>
                                <div class="text-secondary small">vs {{ $m->joiner->name ?? 'Awaiting Opponent' }}</div>
                            </td>
                            <td>
                                @if($m->room_code)
                                    <span class="badge bg-dark border border-warning text-warning font-monospace" style="font-size: 11px;">
                                        {{ $m->room_code }}
                                    </span>
                                @else
                                    <span class="text-secondary small">None</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-light">🪙 {{ number_format($m->entry_fee) }}</span> / 
                                <strong class="text-success">🪙 {{ number_format($m->prize) }}</strong>
                            </td>
                            <td>
                                <span class="badge {{ $m->status === 'completed' ? 'bg-success' : ($m->status === 'running' ? 'bg-primary' : ($m->status === 'cancelled' ? 'bg-danger' : 'bg-secondary')) }}">
                                    {{ strtoupper($m->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $m->review_status === 'disputed' ? 'bg-danger' : ($m->review_status === 'under_review' ? 'bg-warning text-dark' : 'bg-dark border border-secondary text-info') }}">
                                    {{ strtoupper(str_replace('_', ' ', $m->review_status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.ludo.matches.show', $m->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                    Audit &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No matches found matching filter criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $matches->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
