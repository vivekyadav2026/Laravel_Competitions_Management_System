@extends('layouts.admin')

@section('title', 'Direct UPI Deposits — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'UPI QR Deposits Review & Management')

@section('content')
    {{-- Header Action Bar --}}
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="text-secondary small">
            Review incoming player UPI transfer proofs, verify 12-digit UTR references, and credit coins atomically.
        </div>
        <a href="{{ route('admin.ludo.settings.index') }}" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 fw-bold">
            <i class="fa-solid fa-qrcode me-1"></i> Configure QR Payment Gateway
        </a>
    </div>

    {{-- Summary Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Pending Review</div>
                <div class="admin-stat-val text-warning">{{ number_format($pendingCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Requires verification</div>
                <i class="fa-solid fa-clock-rotate-left admin-stat-icon text-warning"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Approved Deposits</div>
                <div class="admin-stat-val text-success">{{ number_format($approvedCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Coins credited</div>
                <i class="fa-solid fa-circle-check admin-stat-icon text-success"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Total Approved (₹)</div>
                <div class="admin-stat-val text-info">₹{{ number_format($totalApprovedAmount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Direct UPI deposits</div>
                <i class="fa-solid fa-indian-rupee-sign admin-stat-icon text-info"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Rejected Deposits</div>
                <div class="admin-stat-val text-danger">{{ number_format($rejectedCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Invalid UTR / Fake</div>
                <i class="fa-solid fa-circle-xmark admin-stat-icon text-danger"></i>
            </div>
        </div>
    </div>

    {{-- Filter & Search Form --}}
    <div class="admin-card mb-4 p-3">
        <form method="GET" action="{{ route('admin.ludo.deposits.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-dark text-secondary border-secondary border-opacity-50">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary border-opacity-50" placeholder="Search by UTR, UID, Name, Phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="status" class="form-select bg-dark text-light border-secondary border-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Only</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved Only</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected Only</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
            </div>
            @if(request()->hasAny(['search', 'status']))
                <div class="col-12 col-md-2">
                    <a href="{{ route('admin.ludo.deposits.index') }}" class="btn btn-outline-secondary w-100">
                        Clear
                    </a>
                </div>
            @endif
        </form>
    </div>

    {{-- Deposits Table --}}
    <div class="admin-card p-0 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead>
                    <tr>
                        <th>Deposit ID</th>
                        <th>User Info</th>
                        <th>Amount</th>
                        <th>UTR / Ref Number</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <th>Date & Time</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposits as $dep)
                        @php
                            $statusBadge = match($dep->status) {
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-warning text-dark'
                            };
                        @endphp
                        <tr>
                            <td>
                                <span class="font-monospace text-info fw-bold">{{ $dep->deposit_uid }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-white">{{ $dep->user->name ?? 'User #' . $dep->user_id }}</div>
                                <div class="small text-secondary">{{ $dep->user->phone ?? 'No Phone' }} &bull; UID: {{ $dep->user->player_uid ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="fw-bold text-success fs-6">₹{{ number_format($dep->amount) }}</span>
                            </td>
                            <td>
                                <span class="font-monospace fw-bold text-warning bg-dark px-2 py-1 rounded border border-secondary border-opacity-50">
                                    {{ $dep->utr_number }}
                                </span>
                            </td>
                            <td>
                                @if($dep->screenshot_path)
                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2 py-0" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#receiptModal{{ $dep->id }}">
                                        <i class="fa-solid fa-image me-1"></i> View Receipt
                                    </button>
                                    {{-- Receipt Preview Modal --}}
                                    <div class="modal fade text-start" id="receiptModal{{ $dep->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content bg-dark text-light border-secondary">
                                                <div class="modal-header border-secondary border-opacity-50">
                                                    <h6 class="modal-title fw-bold text-info">
                                                        <i class="fa-solid fa-receipt me-1"></i> Payment Receipt #{{ $dep->deposit_uid }}
                                                    </h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-center p-3">
                                                    <img src="{{ asset('storage/' . $dep->screenshot_path) }}" alt="Receipt" class="img-fluid rounded border border-secondary shadow-sm mb-2" style="max-height: 450px;">
                                                    <div class="small text-secondary">
                                                        UTR: <strong class="text-warning font-monospace">{{ $dep->utr_number }}</strong> &bull; Amount: <strong class="text-success">₹{{ number_format($dep->amount) }}</strong>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-secondary border-opacity-50 justify-content-between">
                                                    <a href="{{ asset('storage/' . $dep->screenshot_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Original
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-secondary small">None</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge }} text-uppercase" style="font-size: 10px;">
                                    {{ $dep->status }}
                                </span>
                            </td>
                            <td>
                                <div class="small text-light">{{ $dep->created_at ? $dep->created_at->format('d M Y, h:i A') : '-' }}</div>
                                <div class="small text-secondary" style="font-size: 10.5px;">{{ $dep->created_at ? $dep->created_at->diffForHumans() : '' }}</div>
                            </td>
                            <td class="text-end">
                                @if($dep->status === 'pending')
                                    <div class="d-inline-flex gap-1">
                                        {{-- Approve Button --}}
                                        <form action="{{ route('admin.ludo.deposits.approve', $dep->id) }}" method="POST" onsubmit="return confirm('Approve this deposit of ₹{{ $dep->amount }} and credit coins to {{ $dep->user->name ?? 'User' }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold" style="font-size: 11px;">
                                                <i class="fa-solid fa-check me-1"></i> Approve
                                            </button>
                                        </form>

                                        {{-- Reject Button opens Modal --}}
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $dep->id }}">
                                            Reject
                                        </button>
                                    </div>

                                    {{-- Reject Modal --}}
                                    <div class="modal fade text-start" id="rejectModal{{ $dep->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content bg-dark text-light border-secondary">
                                                <div class="modal-header border-secondary border-opacity-50">
                                                    <h5 class="modal-title fs-6 fw-bold text-danger">
                                                        Reject Deposit #{{ $dep->deposit_uid }}
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="{{ route('admin.ludo.deposits.reject', $dep->id) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body small">
                                                        <div class="mb-3">
                                                            <label class="form-label text-light fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                                                            <input type="text" name="reason" class="form-control bg-dark text-light border-secondary" placeholder="e.g. Invalid UTR, Payment not received in bank, duplicate UTR" required minlength="4">
                                                        </div>
                                                        <div class="text-secondary small">
                                                            The user will be notified of this rejection reason in their wallet dashboard.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-secondary border-opacity-50">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-danger fw-bold">Confirm Rejection</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($dep->status === 'approved')
                                    <span class="small text-success">
                                        <i class="fa-solid fa-check-double me-1"></i> Credited by {{ $dep->approver->name ?? 'Admin' }}
                                    </span>
                                @elseif($dep->status === 'rejected')
                                    <span class="small text-danger" title="{{ $dep->rejection_reason }}">
                                        <i class="fa-solid fa-ban me-1"></i> Rejected
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">
                                <i class="fa-solid fa-inbox fs-3 d-block mb-2"></i>
                                No UPI deposits found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deposits->hasPages())
            <div class="p-3 border-top border-secondary border-opacity-25 d-flex justify-content-center">
                {{ $deposits->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>
@endsection
