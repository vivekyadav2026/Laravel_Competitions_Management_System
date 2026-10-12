@extends('layouts.admin')

@section('title', 'Virtual Points Ledger — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Virtual Points Ledger & Balance Auditing')

@section('content')
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.ledger.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search by Tx UID, user name or email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="type" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Transaction Types</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>Deposit (Reload)</option>
                    <option value="entry_fee" {{ request('type') === 'entry_fee' ? 'selected' : '' }}>Battle Entry Fee</option>
                    <option value="prize_payout" {{ request('type') === 'prize_payout' ? 'selected' : '' }}>Prize Payout</option>
                    <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>Refund</option>
                    <option value="referral_bonus" {{ request('type') === 'referral_bonus' ? 'selected' : '' }}>Referral Bonus</option>
                    <option value="admin_adjustment" {{ request('type') === 'admin_adjustment' ? 'selected' : '' }}>Admin Adjustment</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="successful" {{ request('status') === 'successful' ? 'selected' : '' }}>Successful</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter Ledger</button>
                <a href="{{ route('admin.ludo.ledger.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
            </div>
        </form>

        {{-- Ledger Table --}}
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Tx UID</th>
                        <th>Player</th>
                        <th>Type</th>
                        <th>Points Delta</th>
                        <th>Balance Before &rarr; After</th>
                        <th>Description / Mandatory Reason</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                        <tr>
                            <td class="font-monospace text-info small fw-bold">{{ $t->tx_uid }}</td>
                            <td>
                                <div class="text-white small fw-bold">{{ $t->user->name ?? 'User #' . $t->user_id }}</div>
                                <span class="text-secondary small">{{ $t->user->email ?? '' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-light" style="font-size: 10px; text-transform: uppercase;">
                                    {{ str_replace('_', ' ', $t->type) }}
                                </span>
                            </td>
                            <td>
                                <span class="fw-bold {{ $t->points_delta > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $t->points_delta > 0 ? '+' : '' }}{{ number_format($t->points_delta) }}
                                </span>
                            </td>
                            <td class="font-monospace text-secondary small">
                                {{ number_format($t->balance_before) }} &rarr; <span class="text-white fw-bold">{{ number_format($t->balance_after) }}</span>
                            </td>
                            <td class="small" style="max-width: 250px;">
                                <div class="text-truncate text-light" title="{{ $t->description }}">{{ $t->description }}</div>
                                @if($t->reason)
                                    <div class="text-warning text-truncate" style="font-size: 10.5px;" title="Reason: {{ $t->reason }}">
                                        <strong>Reason:</strong> {{ $t->reason }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $t->status === 'successful' ? 'bg-success' : ($t->status === 'failed' ? 'bg-danger' : 'bg-warning text-dark') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                    {{ $t->status }}
                                </span>
                            </td>
                            <td class="text-secondary small">{{ $t->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No ledger transaction records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $transactions->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
