@extends('layouts.admin')

@section('title', 'Referral Management — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Refer & Earn Program Management')

@section('content')
    {{-- Referral Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Total Referrals</div>
                <div class="admin-stat-val text-white">{{ number_format($summary['total_referrals'] ?? 0) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Recorded invite attributions</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Eligible & Rewarded</div>
                <div class="admin-stat-val text-success">{{ number_format($summary['eligible_referrals'] ?? 0) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">
                    Revoked: <span class="text-danger fw-bold">{{ $summary['revoked_referrals'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Virtual Points Awarded</div>
                <div class="admin-stat-val text-warning">🪙 {{ number_format($summary['total_reward_points'] ?? 0) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Non-cash referral bonuses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Duplicate Anomalies</div>
                <div class="admin-stat-val {{ ($summary['duplicate_anomalies'] ?? 0) > 0 ? 'text-danger' : 'text-info' }}">
                    {{ number_format($summary['duplicate_anomalies'] ?? 0) }}
                </div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Duplicate referred user checks</div>
            </div>
        </div>
    </div>

    {{-- Referral Configuration Panel --}}
    <div class="admin-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <h6 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-sliders text-warning me-1"></i> Referral Program Configuration
            </h6>
            <span class="badge {{ ($referralSettings['referral_enabled'] ?? 'on') === 'on' ? 'bg-success' : 'bg-secondary' }}">
                {{ strtoupper($referralSettings['referral_enabled'] ?? 'ON') }}
            </span>
        </div>

        <form action="{{ route('admin.ludo.referrals.settings') }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <div class="col-12 col-md-3">
                <label class="form-label text-secondary small fw-bold mb-1">Referral Program Status</label>
                <select name="referral_enabled" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="on" {{ ($referralSettings['referral_enabled'] ?? 'on') === 'on' ? 'selected' : '' }}>Enabled (Active)</option>
                    <option value="off" {{ ($referralSettings['referral_enabled'] ?? 'on') === 'off' ? 'selected' : '' }}>Disabled (Paused)</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label text-secondary small fw-bold mb-1">Signup Bonus per Referral (Virtual Points)</label>
                <input type="number" name="referral_reward_points" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $referralSettings['referral_reward_points'] ?? 50 }}" min="0" max="5000" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label text-secondary small fw-bold mb-1">Display Commission Label</label>
                <input type="text" name="referral_commission" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $referralSettings['referral_commission'] ?? '3%' }}" required maxlength="20">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-sm btn-warning w-100 rounded-pill fw-bold">
                    <i class="fa-solid fa-save me-1"></i> Save Referral Settings
                </button>
            </div>
        </form>
    </div>

    {{-- Referral Records Table --}}
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.referrals.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search by referral code, referrer, or referred user..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="eligible" {{ request('status') === 'eligible' ? 'selected' : '' }}>Eligible (Rewarded)</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="from" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('from') }}">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="to" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('to') }}">
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.referrals.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>ID / Code</th>
                        <th>Referrer (Inviter)</th>
                        <th>Referred Player</th>
                        <th>Virtual Reward</th>
                        <th>Eligibility Status</th>
                        <th>Timestamp</th>
                        <th>Governance Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($referrals as $ref)
                        @php
                            $isDuplicate = in_array($ref->referred_user_id, $duplicateReferredUserIds ?? []) || ($ref->referrer_id === $ref->referred_user_id);
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-dark border border-warning text-warning font-monospace">{{ $ref->referral_code }}</span>
                                @if($isDuplicate)
                                    <span class="badge bg-danger ms-1" style="font-size: 9px;">DUPLICATE / ANOMALY</span>
                                @endif
                            </td>
                            <td>
                                @if($ref->referrer)
                                    <a href="{{ route('admin.ludo.users.show', $ref->referrer->id) }}" class="text-white small fw-bold text-decoration-none d-block">
                                        {{ $ref->referrer->name }}
                                    </a>
                                    <span class="text-secondary small font-monospace">{{ $ref->referrer->player_uid }}</span>
                                @else
                                    <span class="text-secondary small">User #{{ $ref->referrer_id }}</span>
                                @endif
                            </td>
                            <td>
                                @if($ref->referredUser)
                                    <a href="{{ route('admin.ludo.users.show', $ref->referredUser->id) }}" class="text-info small fw-bold text-decoration-none d-block">
                                        {{ $ref->referredUser->name }}
                                    </a>
                                    <span class="text-secondary small font-monospace">{{ $ref->referredUser->player_uid }}</span>
                                @else
                                    <span class="text-secondary small">User #{{ $ref->referred_user_id }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-warning fw-bold">🪙 +{{ number_format($ref->reward_points) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $ref->status === 'eligible' ? 'bg-success' : ($ref->status === 'revoked' ? 'bg-danger' : 'bg-warning text-dark') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                    {{ $ref->status }}
                                </span>
                            </td>
                            <td class="text-secondary small">
                                {{ $ref->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td>
                                <form action="{{ route('admin.ludo.referrals.status', $ref->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ $ref->status === 'eligible' ? 'revoked' : 'eligible' }}">
                                    <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Audit reason..." required minlength="5" style="font-size: 10.5px; max-width: 130px;">
                                    <button type="submit" class="btn btn-sm {{ $ref->status === 'eligible' ? 'btn-outline-danger' : 'btn-outline-success' }} rounded-pill py-0 px-2" style="font-size: 10.5px;">
                                        {{ $ref->status === 'eligible' ? 'Revoke' : 'Approve' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No referral records found matching your criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $referrals->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
