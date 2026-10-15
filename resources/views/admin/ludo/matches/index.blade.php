@extends('layouts.admin')

@section('title', 'Matches & Results — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Match Management & Auditing')

@section('content')
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.matches.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Battle UID, room code, player..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>Running</option>
                    <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <select name="review_status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Reviews</option>
                    <option value="under_review" {{ request('review_status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                    <option value="disputed" {{ request('review_status') === 'disputed' ? 'selected' : '' }}>Disputed</option>
                    <option value="auto_approved" {{ request('review_status') === 'auto_approved' ? 'selected' : '' }}>Auto Approved</option>
                    <option value="verified" {{ request('review_status') === 'verified' ? 'selected' : '' }}>Verified</option>
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="from" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('from') }}" title="From Date">
            </div>
            <div class="col-6 col-sm-3 col-md-1">
                <input type="date" name="to" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('to') }}" title="To Date">
            </div>
            <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.matches.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Reset</a>
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
                <tbody id="adminMatchesTableBody">
                    @forelse($matches as $m)
                        @php
                            $statusInfo = $m->getFormattedStatusBadge();
                            $badgeInfo = $m->getFormattedReviewBadge();
                        @endphp
                        <tr id="matchRow{{ $m->id }}">
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
                                    <span class="badge bg-dark border border-warning text-warning font-monospace match-room-code-badge" style="font-size: 11px;">
                                        {{ $m->room_code }}
                                    </span>
                                @else
                                    <span class="text-secondary small match-room-code-badge">None</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-light">🪙 {{ number_format($m->entry_fee) }}</span> / 
                                <strong class="text-success">🪙 {{ number_format($m->prize) }}</strong>
                            </td>
                            <td>
                                <span class="badge match-status-badge {{ $statusInfo['class'] }}">
                                    {{ $statusInfo['text'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge match-review-badge {{ $badgeInfo['class'] }}" style="font-size: 10.5px;">
                                    {{ $badgeInfo['text'] }}
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('adminMatchesTableBody');
    let isFetchingMatches = false;

    async function refreshMatchesLiveFeed() {
        if (document.hidden || isFetchingMatches) return;
        isFetchingMatches = true;

        try {
            const res = await fetch("{{ route('admin.ludo.matches.liveFeed') }}?_t=" + Date.now(), {
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
                if (data && data.success && Array.isArray(data.matches)) {
                    data.matches.forEach(function(m) {
                        const row = document.getElementById('matchRow' + m.id);
                        if (row) {
                            // Update room code
                            const roomBadge = row.querySelector('.match-room-code-badge');
                            if (roomBadge && m.room_code) {
                                roomBadge.className = 'badge bg-dark border border-warning text-warning font-monospace match-room-code-badge';
                                roomBadge.textContent = m.room_code;
                            }

                            // Update match status badge
                            const statusBadge = row.querySelector('.match-status-badge');
                            if (statusBadge) {
                                const st = m.status;
                                const cls = m.status_badge_class || (st === 'completed' ? 'bg-success' : (st === 'running' ? 'bg-primary' : (st === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark')));
                                statusBadge.className = 'badge match-status-badge ' + cls;
                                statusBadge.textContent = m.status_badge_text || st.toUpperCase();
                            }

                            // Update review status badge
                            const reviewBadge = row.querySelector('.match-review-badge');
                            if (reviewBadge) {
                                const rev = m.review_status || 'none';
                                const rcls = m.review_badge_class || (rev === 'disputed' ? 'bg-danger' : (rev === 'under_review' ? 'bg-warning text-dark' : 'bg-dark border border-secondary text-info'));
                                reviewBadge.className = 'badge match-review-badge ' + rcls;
                                reviewBadge.textContent = m.review_badge_text || rev.replace('_', ' ').toUpperCase();
                            }
                        } else if (tableBody) {
                            // Insert new match row dynamically without reload
                            const st = m.status;
                            const cls = m.status_badge_class || (st === 'completed' ? 'bg-success' : (st === 'running' ? 'bg-primary' : (st === 'cancelled' ? 'bg-danger' : 'bg-warning text-dark')));
                            const statusText = m.status_badge_text || st.toUpperCase();
                            const rev = m.review_status || 'none';
                            const rcls = m.review_badge_class || (rev === 'disputed' ? 'bg-danger' : (rev === 'under_review' ? 'bg-warning text-dark' : 'bg-dark border border-secondary text-info'));
                            const reviewText = m.review_badge_text || rev.replace('_', ' ').toUpperCase();
                            const roomHtml = m.room_code ? `<span class="badge bg-dark border border-warning text-warning font-monospace match-room-code-badge" style="font-size: 11px;">${m.room_code}</span>` : `<span class="text-secondary small match-room-code-badge">None</span>`;

                            const newRowHtml = `
                                <tr id="matchRow${m.id}" class="animate__animated animate__fadeIn table-warning">
                                    <td class="font-monospace text-info small">
                                        <div>${m.battle_uid}</div>
                                        <span class="text-secondary" style="font-size: 10px;">${m.created_at_formatted || 'Just now'}</span>
                                    </td>
                                    <td>
                                        <div class="text-white small fw-bold">${m.creator_name || 'P1'}</div>
                                        <div class="text-secondary small">vs ${m.joiner_name || 'Awaiting Opponent'}</div>
                                    </td>
                                    <td>${roomHtml}</td>
                                    <td>
                                        <span class="text-light">🪙 ${Number(m.entry_fee).toLocaleString()}</span> / 
                                        <strong class="text-success">🪙 ${Number(m.prize).toLocaleString()}</strong>
                                    </td>
                                    <td>
                                        <span class="badge match-status-badge ${cls}">${statusText}</span>
                                    </td>
                                    <td>
                                        <span class="badge match-review-badge ${rcls}" style="font-size: 10.5px;">${reviewText}</span>
                                    </td>
                                    <td>
                                        <a href="${m.audit_url}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                            Audit &rarr;
                                        </a>
                                    </td>
                                </tr>
                            `;
                            tableBody.insertAdjacentHTML('afterbegin', newRowHtml);
                            const insertedRow = document.getElementById('matchRow' + m.id);
                            setTimeout(() => {
                                if (insertedRow) insertedRow.classList.remove('table-warning');
                            }, 3000);
                        }
                    });
                }
            }
        } catch (err) {
            console.debug('Matches live feed error:', err);
        } finally {
            isFetchingMatches = false;
        }
    }

    window.addEventListener('admin:matches-alert', refreshMatchesLiveFeed);
    window.addEventListener('admin:dispute-alert', refreshMatchesLiveFeed);
    setInterval(refreshMatchesLiveFeed, 3500);
});
</script>
@endpush
@endsection
