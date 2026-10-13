@extends('layouts.admin')

@section('title', 'Player Dossier — ' . $user->name)
@section('page_title', 'Player Dossier: ' . $user->name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.ludo.users.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Back to Users List
        </a>
    </div>

    {{-- Top Profile Header & Action Panels --}}
    <div class="row g-3 mb-4">
        {{-- Profile Card --}}
        <div class="col-12 col-lg-8">
            <div class="admin-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-dark text-info fw-bold fs-4 border border-info" style="width: 54px; height: 54px;">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-0">{{ $user->name }}</h5>
                            <span class="text-secondary small font-monospace">UID: {{ $user->player_uid ?? 'LUDO' . str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-danger' }} fs-6 mb-1">
                            {{ strtoupper($user->status) }}
                        </span>
                        <div class="text-secondary small">Role: <strong class="text-info">{{ strtoupper($user->role) }}</strong></div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Email</div>
                        <div class="text-white small fw-bold">{{ $user->email }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Phone</div>
                        <div class="text-white small fw-bold">{{ $user->phone ?? 'Not set' }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Virtual Balance</div>
                        <div class="text-warning small fw-bold">🪙 {{ number_format($user->coins) }} Points</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">KYC Status</div>
                        <div class="text-info small fw-bold text-uppercase">{{ $user->kyc_status }}</div>
                    </div>
                </div>

                {{-- Match Statistics Summary --}}
                <div class="row g-2 mt-3 pt-3 border-top border-secondary border-opacity-25 text-center">
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Matches Played</div>
                        <div class="text-white fw-bold">{{ $battleStats['total'] ?? 0 }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Won / Lost</div>
                        <div class="fw-bold">
                            <span class="text-success">{{ $battleStats['won'] ?? 0 }}W</span> /
                            <span class="text-danger">{{ $battleStats['lost'] ?? 0 }}L</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Win Rate</div>
                        <div class="text-info fw-bold">{{ $battleStats['win_rate'] ?? 0 }}%</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Virtual Prizes Won</div>
                        <div class="text-warning fw-bold">🪙 {{ number_format($battleStats['points_earned'] ?? 0) }}</div>
                    </div>
                </div>

                @if($user->status === 'suspended' && $user->suspension_reason)
                    <div class="mt-3 p-2 rounded-2 bg-danger bg-opacity-25 border border-danger text-danger small">
                        <strong>Suspension Reason:</strong> {{ $user->suspension_reason }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Administrative Actions Panel --}}
        <div class="col-12 col-lg-4">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-user-shield text-warning me-1"></i> Governance Controls
                </h6>

                {{-- Suspend / Reactivate Form --}}
                <form action="{{ route('admin.ludo.users.status', $user->id) }}" method="POST" class="mb-3">
                    @csrf
                    <input type="hidden" name="action" value="{{ $user->status === 'active' ? 'suspend' : 'reactivate' }}">
                    <div class="mb-2">
                        <label class="form-label text-secondary small fw-bold mb-1">
                            {{ $user->status === 'active' ? 'Mandatory Suspension Reason' : 'Reactivation Note' }}
                        </label>
                        <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Reason for action..." required minlength="5">
                    </div>
                    <button type="submit" class="btn btn-sm {{ $user->status === 'active' ? 'btn-danger' : 'btn-success' }} w-100 rounded-pill fw-bold" onclick="return confirm('Confirm administrative status toggle?')">
                        {{ $user->status === 'active' ? 'Suspend Account' : 'Reactivate Account' }}
                    </button>
                </form>

                {{-- Role Change Form --}}
                <form action="{{ route('admin.ludo.users.role', $user->id) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label text-secondary small fw-bold mb-1">Assign Permitted Role</label>
                        <select name="role" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="normal" {{ in_array($user->role, ['normal', 'user']) ? 'selected' : '' }}>Player (Normal)</option>
                            <option value="support_staff" {{ $user->role === 'support_staff' ? 'selected' : '' }}>Support Staff</option>
                            <option value="moderator" {{ $user->role === 'moderator' ? 'selected' : '' }}>Moderator</option>
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                            @if(auth()->user() && auth()->user()->role === 'superadmin')
                                <option value="superadmin" {{ $user->role === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                            @endif
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-info w-100 rounded-pill">
                        Update Role
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Two Column: Permitted Profile Corrections & Referral Dossier --}}
    <div class="row g-3 mb-4">
        {{-- Permitted Profile Corrections --}}
        <div class="col-12 col-lg-6">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-user-pen text-info me-1"></i> Permitted Profile Corrections (Audited)
                </h6>
                <form action="{{ route('admin.ludo.users.profile', $user->id) }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-12 col-md-6">
                        <label class="form-label text-secondary small fw-bold mb-1">Display Name</label>
                        <input type="text" name="name" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $user->name }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-secondary small fw-bold mb-1">Phone Number</label>
                        <input type="text" name="phone" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $user->phone }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold mb-1">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ $user->email }}" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label text-secondary small fw-bold mb-1">Mandatory Audit Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Explain why profile details are being corrected..." required minlength="5">
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold">
                            Save Profile Correction
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Referral Information --}}
        <div class="col-12 col-lg-6">
            <div class="admin-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-gift text-danger me-1"></i> Referral Attribution & Invites
                    </h6>
                    <span class="badge bg-dark border border-secondary text-warning font-monospace">
                        Code: {{ $user->referral_code ?? 'None' }}
                    </span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="text-secondary small">Invited By (Referrer)</div>
                        <div class="text-white small fw-bold">
                            @if($user->referrer)
                                <a href="{{ route('admin.ludo.users.show', $user->referrer->id) }}" class="text-info text-decoration-none">
                                    {{ $user->referrer->name }} ({{ $user->referrer->player_uid }})
                                </a>
                            @else
                                Organic Registration
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small">Referral Bonus Earned</div>
                        <div class="text-warning small fw-bold">🪙 {{ number_format($user->referral_balance ?? 0) }} Points ({{ $user->referrals->count() }} Invites)</div>
                    </div>
                </div>

                @if($user->referrals->count() > 0)
                    <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Referred Player</th>
                                    <th>Reward</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->referrals as $ref)
                                    <tr>
                                        <td class="small">
                                            <a href="{{ route('admin.ludo.users.show', $ref->referred_user_id) }}" class="text-info text-decoration-none">
                                                {{ $ref->referredUser->name ?? 'User #' . $ref->referred_user_id }}
                                            </a>
                                        </td>
                                        <td class="text-warning small fw-bold">+{{ $ref->reward_points }} 🪙</td>
                                        <td><span class="badge bg-success" style="font-size: 9px;">{{ strtoupper($ref->status) }}</span></td>
                                        <td class="text-secondary small">{{ $ref->created_at->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-secondary py-2 small">No players invited by this user yet.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Match History Dossier --}}
    <div class="admin-card mb-4">
        <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-gamepad text-success me-1"></i> Recent Match Records
        </h6>

        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Battle Ref</th>
                        <th>Role</th>
                        <th>Opponent</th>
                        <th>Entry / Prize</th>
                        <th>Status</th>
                        <th>Review</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($battles as $b)
                        @php
                            $isCreator = ($b->creator_id === $user->id);
                            $opponent = $isCreator ? $b->joiner : $b->creator;
                        @endphp
                        <tr>
                            <td class="font-monospace text-info">{{ $b->battle_uid }}</td>
                            <td><span class="badge bg-dark border border-secondary">{{ $isCreator ? 'Creator' : 'Joiner' }}</span></td>
                            <td>{{ $opponent->name ?? 'Awaiting Opponent' }}</td>
                            <td>🪙 {{ $b->entry_fee }} / 🪙 {{ $b->prize }}</td>
                            <td><span class="badge {{ $b->status === 'completed' ? 'bg-success' : ($b->status === 'running' ? 'bg-primary' : 'bg-secondary') }}">{{ $b->status }}</span></td>
                            <td><span class="badge bg-dark text-warning border border-secondary">{{ $b->review_status }}</span></td>
                            <td class="text-secondary small">{{ $b->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-3">No match records found for this player.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $battles->links('pagination::bootstrap-5') }}</div>
    </div>

    {{-- KYC Verification Dossier & Review Actions --}}
    <div class="admin-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <h6 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-id-card text-info me-1"></i> KYC Verification Documents
            </h6>
            <span class="badge {{ $user->kyc_status === 'verified' ? 'bg-success' : ($user->kyc_status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }} text-uppercase">
                {{ $user->kyc_status ?? 'UNVERIFIED' }}
            </span>
        </div>

        @if($user->kycSubmissions->count() > 0)
            <div class="d-flex flex-column gap-3">
                @foreach($user->kycSubmissions as $sub)
                    <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08);">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-2">
                            <div>
                                <span class="badge bg-dark border border-secondary text-info text-uppercase me-2">
                                    {{ str_replace('_', ' ', $sub->document_type) }}
                                </span>
                                <strong class="text-white font-monospace">{{ $sub->document_number }}</strong>
                                <span class="text-secondary small ms-2">Submitted: {{ $sub->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div>
                                <span class="badge {{ $sub->status === 'approved' ? 'bg-success' : ($sub->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }} text-uppercase">
                                    {{ $sub->status }}
                                </span>
                            </div>
                        </div>

                        {{-- Download Secure Links --}}
                        <div class="d-flex flex-wrap gap-2 my-2">
                            @if($sub->front_image_path)
                                <a href="{{ route('ludo.kyc.download', [$sub->id, 'front']) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                    <i class="fa-solid fa-file-image me-1"></i> Download Front Document
                                </a>
                            @endif
                            @if($sub->back_image_path)
                                <a href="{{ route('ludo.kyc.download', [$sub->id, 'back']) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                    <i class="fa-solid fa-file-image me-1"></i> Download Back Document
                                </a>
                            @endif
                        </div>

                        @if($sub->admin_reason)
                            <div class="text-secondary small mt-1">
                                <strong>Admin Rationale:</strong> {{ $sub->admin_reason }}
                            </div>
                        @endif

                        {{-- Review Form if pending or rejected --}}
                        @if($sub->status !== 'approved')
                            <div class="mt-3 pt-2 border-top border-secondary border-opacity-25">
                                <form action="{{ route('ludo.admin.kyc.review', $sub->id) }}" method="POST" class="row g-2 align-items-center">
                                    @csrf
                                    <div class="col-12 col-md-3">
                                        <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                            <option value="approved">Approve & Verify Player</option>
                                            <option value="rejected">Reject Submission</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <input type="text" name="admin_reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Mandatory verification notes / rejection reason..." required minlength="5">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <button type="submit" class="btn btn-sm btn-info w-100 rounded-pill fw-bold" onclick="return confirm('Confirm KYC review verdict?')">
                                            Submit Review
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-secondary py-3 small">
                No KYC verification documents submitted by this user.
            </div>
        @endif
    </div>

    {{-- Two Column: Disputes & Support Inquiries --}}
    <div class="row g-3 mb-4">
        {{-- Disputes involving player --}}
        <div class="col-12 col-lg-6">
            <div class="admin-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-gavel text-danger me-1"></i> Disputes Involving Player
                    </h6>
                    <span class="badge bg-secondary">{{ count($disputes) }}</span>
                </div>

                @if(count($disputes) > 0)
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Dispute UID</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($disputes as $disp)
                                    <tr>
                                        <td class="font-monospace text-danger small">{{ $disp->dispute_uid }}</td>
                                        <td class="small">{{ ucwords(str_replace('_', ' ', $disp->reason_category)) }}</td>
                                        <td>
                                            <span class="badge {{ $disp->status === 'resolved' ? 'bg-success' : ($disp->status === 'open' ? 'bg-danger' : 'bg-warning text-dark') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                                {{ str_replace('_', ' ', $disp->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('ludo.dispute.view', $disp->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10px;">
                                                View &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-secondary py-3 small">No disputes involving this player.</div>
                @endif
            </div>
        </div>

        {{-- Support Inquiries --}}
        <div class="col-12 col-lg-6">
            <div class="admin-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <h6 class="text-white fw-bold mb-0">
                        <i class="fa-solid fa-headset text-info me-1"></i> Support Ticket Inquiries
                    </h6>
                    <span class="badge bg-secondary">{{ count($tickets) }}</span>
                </div>

                @if(count($tickets) > 0)
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead>
                                <tr>
                                    <th>Ticket UID</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $t)
                                    <tr>
                                        <td class="font-monospace text-info small">{{ $t->ticket_uid }}</td>
                                        <td class="small text-truncate" style="max-width: 140px;">{{ $t->subject }}</td>
                                        <td>
                                            <span class="badge {{ $t->status === 'open' ? 'bg-primary' : ($t->status === 'answered' ? 'bg-success' : 'bg-secondary') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                                {{ str_replace('_', ' ', $t->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('ludo.ticket.view', $t->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10px;">
                                                Reply &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-secondary py-3 small">No support tickets created by this player.</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Virtual Points Ledger History --}}
    <div class="admin-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <h6 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-receipt text-warning me-1"></i> Recent Virtual Points Ledger History
            </h6>
            <a href="{{ route('admin.ludo.ledger.index', ['search' => $user->player_uid ?? $user->email]) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                Full Ledger &rarr;
            </a>
        </div>

        @if(count($transactions) > 0)
            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th>Tx UID</th>
                            <th>Type</th>
                            <th>Points Delta</th>
                            <th>Balance After</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $tx)
                            <tr>
                                <td class="font-monospace text-info small">{{ $tx->tx_uid }}</td>
                                <td class="small text-capitalize">{{ str_replace('_', ' ', $tx->type) }}</td>
                                <td class="fw-bold {{ $tx->points_delta >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $tx->points_delta >= 0 ? '+' : '' }}{{ number_format($tx->points_delta) }} 🪙
                                </td>
                                <td class="text-white small">🪙 {{ number_format($tx->balance_after) }}</td>
                                <td>
                                    <span class="badge {{ $tx->status === 'successful' ? 'bg-success' : 'bg-danger' }}" style="font-size: 9.5px;">
                                        {{ strtoupper($tx->status) }}
                                    </span>
                                </td>
                                <td class="small text-secondary">{{ Str::limit($tx->description, 50) }}</td>
                                <td class="small text-secondary">{{ $tx->created_at->format('d M, h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-secondary py-3 small">No ledger transactions found for this player.</div>
        @endif
    </div>

    {{-- Virtual Points Ledger Adjustments Modal / Quick Form --}}
    <div class="admin-card">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <h6 class="text-white fw-bold mb-0">
                <i class="fa-solid fa-coins text-warning me-1"></i> Administrative Balance Adjustment
            </h6>
            <span class="badge bg-secondary">Row-locked Ledger</span>
        </div>

        <form action="{{ route('admin.ludo.ledger.adjust') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">
            <div class="col-12 col-md-4">
                <label class="form-label text-secondary small fw-bold mb-1">Points Delta (Positive for credit, Negative for debit)</label>
                <input type="number" name="amount" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="e.g. +500 or -200" required>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label text-secondary small fw-bold mb-1">Mandatory Audit Justification</label>
                <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Explain the adjustment reason..." required minlength="8">
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-sm btn-warning w-100 rounded-pill fw-bold" onclick="return confirm('Confirm ledger points adjustment?')">
                    Execute Adjustment
                </button>
            </div>
        </form>
    </div>
@endsection
