@extends('layouts.admin')

@section('title', 'Virtual Points Ledger — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Virtual Points Ledger & Balance Auditing')

@section('content')
    {{-- Non-Cash Virtual Points Overview Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Player Wallet Balances</div>
                <div class="admin-stat-val text-warning">🪙 {{ number_format($summary['total_user_balances'] ?? 0) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Total circulating virtual points</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Points Credited (Deposits)</div>
                <div class="admin-stat-val text-success">+{{ number_format($summary['total_deposits'] ?? 0) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Free reloads & welcome bonuses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Prizes & Referrals Paid</div>
                <div class="admin-stat-val text-info">🪙 {{ number_format(($summary['total_prizes'] ?? 0) + ($summary['total_referrals'] ?? 0)) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">
                    Prizes: {{ number_format($summary['total_prizes'] ?? 0) }} &bull; Ref: {{ number_format($summary['total_referrals'] ?? 0) }}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Entry Fees & Refunds</div>
                <div class="admin-stat-val text-light">🪙 {{ number_format($summary['total_entry_fees'] ?? 0) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">
                    Refunded: <span class="text-info fw-bold">🪙 {{ number_format($summary['total_refunds'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Authorized Balance Adjustment Panel --}}
    <div class="admin-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <h6 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-coins text-warning me-1"></i> Authorized Virtual-Points Balance Adjustment
            </h6>
            <span class="badge bg-dark border border-warning text-warning" style="font-size: 10px;">Non-Cash Virtual Points Only &bull; Row-Locked Atomic Ledger</span>
        </div>

        <form action="{{ route('admin.ludo.ledger.adjust') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-4">
                <label class="form-label text-secondary small fw-bold mb-1">Select Player Account <span class="text-danger">*</span></label>
                <select name="user_id" class="form-select form-select-sm bg-dark text-light border-secondary" required>
                    <option value="">-- Choose Player --</option>
                    @foreach($usersList ?? [] as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->player_uid ?? 'ID#' . $u->id }}) — Bal: 🪙 {{ number_format($u->coins) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label text-secondary small fw-bold mb-1">Points Delta (+/-) <span class="text-danger">*</span></label>
                <input type="number" name="amount" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="e.g. +500 or -200" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label text-secondary small fw-bold mb-1">Mandatory Audit Reason (Min 8 chars) <span class="text-danger">*</span></label>
                <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Mandatory explanation for audit log..." required minlength="8">
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-warning w-100 rounded-pill fw-bold" onclick="return confirm('Execute this virtual-points balance adjustment?')">
                    Execute Adjustment
                </button>
            </div>
        </form>
    </div>

    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.ledger.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Tx UID, player name, email..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="type" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Types</option>
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
            <div class="col-6 col-md-2">
                <input type="date" name="from" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('from') }}" title="From Date">
            </div>
            <div class="col-6 col-md-1">
                <input type="date" name="to" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('to') }}" title="To Date">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
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
                                @if($t->user)
                                    <a href="{{ route('admin.ludo.users.show', $t->user->id) }}" class="text-white small fw-bold text-decoration-none d-block">
                                        {{ $t->user->name }}
                                    </a>
                                    <span class="text-secondary small">{{ $t->user->email }}</span>
                                @else
                                    <div class="text-white small fw-bold">User #{{ $t->user_id }}</div>
                                @endif
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
                            <td class="small" style="max-width: 260px;">
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
