@extends('layouts.admin')

@section('title', 'Player Withdrawals & Settlements — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Player Payout & Settlement Desk')

@section('content')
    {{-- Header Action Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="text-secondary small">
            Review incoming withdrawal requests, copy player UPI / Bank coordinates, transfer funds, and enter payout reference (UTR) to mark as settled.
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1 small text-wrap text-start" style="line-height: 1.4;">
                <i class="fa-solid fa-bolt me-1"></i> Instant Player Settlement Workflow Active
            </span>
        </div>
    </div>

    {{-- Summary Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Pending Settlement</div>
                <div class="admin-stat-val text-warning">{{ number_format($pendingCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">
                    Amount: <strong class="text-warning">₹{{ number_format($pendingAmount) }}</strong>
                </div>
                <i class="fa-solid fa-hourglass-half admin-stat-icon text-warning"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Settled Today</div>
                <div class="admin-stat-val text-success">₹{{ number_format($settledTodayAmount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Transferred today</div>
                <i class="fa-solid fa-calendar-check admin-stat-icon text-success"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Total Settled (₹)</div>
                <div class="admin-stat-val text-info">₹{{ number_format($totalSettledAmount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">{{ number_format($approvedCount) }} requests paid</div>
                <i class="fa-solid fa-indian-rupee-sign admin-stat-icon text-info"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Rejected / Refunded</div>
                <div class="admin-stat-val text-danger">{{ number_format($rejectedCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Coins restored to wallet</div>
                <i class="fa-solid fa-rotate-left admin-stat-icon text-danger"></i>
            </div>
        </div>
    </div>

    {{-- Filter & Search Form --}}
    <div class="admin-card mb-4 p-3">
        <form method="GET" action="{{ route('admin.ludo.withdrawals.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-dark text-secondary border-secondary border-opacity-50">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary border-opacity-50" placeholder="Search by UID, UPI, Player, UTR..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <select name="status" class="form-select bg-dark text-light border-secondary border-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Only</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Settled / Approved Only</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected Only</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-warning fw-bold flex-grow-1">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.ludo.withdrawals.index') }}" class="btn btn-outline-secondary px-3">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Withdrawals Table --}}
    <div class="admin-card p-0 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>Withdrawal ID</th>
                        <th>Player Details</th>
                        <th>Amount (₹)</th>
                        <th>Payout Destination</th>
                        <th>Status</th>
                        <th>Payout UTR / Notes</th>
                        <th>Date & Time</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $wth)
                        @php
                            $statusBadge = match($wth->status) {
                                'approved' => 'bg-success text-white',
                                'rejected' => 'bg-danger text-white',
                                default => 'bg-warning text-dark'
                            };
                            $statusLabel = match($wth->status) {
                                'approved' => 'Settled & Paid',
                                'rejected' => 'Rejected & Refunded',
                                default => 'Pending Settlement'
                            };
                        @endphp
                        <tr>
                            <td>
                                <strong class="font-monospace text-warning">{{ $wth->withdrawal_uid }}</strong>
                            </td>
                            <td>
                                @if($wth->user)
                                    <div class="fw-bold text-white">{{ $wth->user->name }}</div>
                                    <div class="small text-secondary" style="font-size: 11px;">
                                        UID: <span class="text-info font-monospace">{{ $wth->user->player_uid ?? ('LUDO' . str_pad($wth->user->id, 5, '0', STR_PAD_LEFT)) }}</span> &bull;
                                        <i class="fa-solid fa-phone text-muted ms-1"></i> {{ $wth->user->phone ?? 'No phone' }}
                                    </div>
                                @else
                                    <span class="text-danger small">Deleted User #{{ $wth->user_id }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold fs-6 text-white">₹{{ number_format($wth->amount) }}</span>
                                <div class="text-secondary small" style="font-size: 11px;">🪙 {{ number_format($wth->amount) }} coins</div>
                            </td>
                            <td>
                                @if($wth->payout_method === 'upi' || !empty($wth->upi_id))
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge bg-dark border border-secondary text-info font-monospace px-2 py-1" id="upi-{{ $wth->id }}">
                                            {{ $wth->upi_id }}
                                        </span>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-info p-1 py-0" 
                                            title="Copy UPI ID"
                                            onclick="navigator.clipboard.writeText('{{ $wth->upi_id }}'); alert('Copied UPI ID: {{ $wth->upi_id }}');"
                                        >
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                    <div class="text-secondary small mt-1" style="font-size: 10px;">Direct UPI Transfer</div>
                                @else
                                    <div class="text-white small fw-bold">{{ $wth->account_holder_name }}</div>
                                    <div class="font-monospace text-warning small">{{ $wth->bank_account_number }}</div>
                                    <div class="text-secondary small" style="font-size: 10px;">IFSC: {{ $wth->bank_ifsc }} &bull; {{ $wth->bank_name }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge }} badge-status">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                @if($wth->status === 'approved')
                                    <div class="small text-success">
                                        <i class="fa-solid fa-check-double me-1"></i> UTR: <strong class="font-monospace">{{ $wth->admin_utr_reference ?? 'N/A' }}</strong>
                                    </div>
                                    @if($wth->processor)
                                        <div class="text-secondary" style="font-size: 10.5px;">By: {{ $wth->processor->name }}</div>
                                    @endif
                                @elseif($wth->status === 'rejected')
                                    <div class="small text-danger">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $wth->admin_notes ?? 'Invalid payout details' }}
                                    </div>
                                    <div class="text-secondary" style="font-size: 10.5px;">Points refunded to player</div>
                                @else
                                    <span class="text-warning small">
                                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Ready for transfer
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-light">{{ $wth->created_at ? $wth->created_at->format('d M Y') : '—' }}</div>
                                <div class="text-secondary small" style="font-size: 11px;">{{ $wth->created_at ? $wth->created_at->format('h:i A') : '' }}</div>
                            </td>
                            <td class="text-end">
                                @if($wth->status === 'pending')
                                    <div class="d-inline-flex gap-1">
                                        {{-- Settle / Pay Button --}}
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-success fw-bold px-2 py-1 shadow-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#settleModal{{ $wth->id }}"
                                        >
                                            <i class="fa-solid fa-money-bill-wave me-1"></i> Settle
                                        </button>
                                        {{-- Reject Button --}}
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-danger px-2 py-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#rejectModal{{ $wth->id }}"
                                        >
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>

                                    {{-- Settlement Modal --}}
                                    <div class="modal fade" id="settleModal{{ $wth->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content bg-dark text-light border-warning border-opacity-25">
                                                <form action="{{ route('admin.ludo.withdrawals.approve', $wth->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-secondary border-opacity-25">
                                                        <h5 class="modal-title fs-6 fw-bold text-warning">
                                                            <i class="fa-solid fa-paper-plane me-1"></i> Settle Payout — {{ $wth->withdrawal_uid }}
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="p-3 rounded-3 mb-3" style="background: rgba(16, 25, 46, 0.9); border: 1px solid rgba(212, 175, 55, 0.3);">
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <span class="text-secondary small">Player:</span>
                                                                <strong class="text-white">{{ $wth->user->name ?? 'Player' }} ({{ $wth->user->phone ?? '' }})</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                <span class="text-secondary small">Transfer Amount:</span>
                                                                <strong class="text-success fs-5">₹{{ number_format($wth->amount) }}</strong>
                                                            </div>
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span class="text-secondary small">Pay to UPI:</span>
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <strong class="font-monospace text-info">{{ $wth->upi_id ?? $wth->bank_account_number }}</strong>
                                                                    @if($wth->upi_id)
                                                                        <button type="button" class="btn btn-sm btn-outline-info py-0 px-1" onclick="navigator.clipboard.writeText('{{ $wth->upi_id }}'); alert('Copied!');">
                                                                            <i class="fa-solid fa-copy"></i>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="alert alert-info py-2 px-3 small rounded-3 mb-3">
                                                            <i class="fa-solid fa-circle-info me-1"></i>
                                                            Send <strong>₹{{ number_format($wth->amount) }}</strong> to player's UPI/Account using GPay/PhonePe/Paytm, then enter the payment UTR below.
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label text-warning small fw-bold">Bank/UPI Settlement UTR Number <span class="text-danger">*</span></label>
                                                            <input type="text" name="admin_utr_reference" class="form-control bg-black text-light border-secondary font-monospace" placeholder="e.g. 509812345678" required minlength="6" maxlength="64">
                                                            <div class="text-secondary small mt-1" style="font-size: 11px;">The 12-digit UPI reference ID generated upon your successful transfer.</div>
                                                        </div>

                                                        <div class="mb-2">
                                                            <label class="form-label text-secondary small">Optional Admin Remarks</label>
                                                            <input type="text" name="admin_notes" class="form-control bg-black text-light border-secondary" placeholder="e.g. Sent via PhonePe business UPI">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-secondary border-opacity-25">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                                                            <i class="fa-solid fa-check me-1"></i> Confirm &amp; Mark as Settled
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Rejection Modal --}}
                                    <div class="modal fade" id="rejectModal{{ $wth->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content bg-dark text-light border-danger border-opacity-25">
                                                <form action="{{ route('admin.ludo.withdrawals.reject', $wth->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header border-secondary border-opacity-25">
                                                        <h5 class="modal-title fs-6 fw-bold text-danger">
                                                            <i class="fa-solid fa-ban me-1"></i> Reject &amp; Refund — {{ $wth->withdrawal_uid }}
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning py-2 px-3 small rounded-3 mb-3">
                                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                            Rejecting will immediately refund <strong>🪙 {{ number_format($wth->amount) }} coins</strong> back into {{ $wth->user->name ?? 'Player' }}'s wallet.
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label text-warning small fw-bold">Rejection Reason <span class="text-danger">*</span></label>
                                                            <textarea name="reason" class="form-control bg-black text-light border-secondary" rows="3" placeholder="e.g. Invalid UPI ID / Name mismatch / Please verify KYC before withdrawal." required minlength="3"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-secondary border-opacity-25">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-danger fw-bold px-3">
                                                            <i class="fa-solid fa-rotate-left me-1"></i> Reject &amp; Refund Coins
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-secondary small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-secondary">
                                <i class="fa-solid fa-money-bill-transfer fs-1 mb-2 d-block opacity-25"></i>
                                No withdrawal requests found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
            <div class="p-3 border-top border-secondary border-opacity-25">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
@endsection
