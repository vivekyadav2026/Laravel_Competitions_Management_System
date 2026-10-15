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
                <div class="admin-stat-val text-warning" id="statPendingCount">{{ number_format($pendingCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Requires verification</div>
                <i class="fa-solid fa-clock-rotate-left admin-stat-icon text-warning"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Approved Deposits</div>
                <div class="admin-stat-val text-success" id="statApprovedCount">{{ number_format($approvedCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Coins credited</div>
                <i class="fa-solid fa-circle-check admin-stat-icon text-success"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Total Approved (₹)</div>
                <div class="admin-stat-val text-info" id="statTotalApprovedAmount">₹{{ number_format($totalApprovedAmount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Direct UPI deposits</div>
                <i class="fa-solid fa-indian-rupee-sign admin-stat-icon text-info"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Rejected Deposits</div>
                <div class="admin-stat-val text-danger" id="statRejectedCount">{{ number_format($rejectedCount) }}</div>
                <div class="small text-secondary mt-1" style="font-size: 11px;">Invalid UTR / Fake</div>
                <i class="fa-solid fa-circle-xmark admin-stat-icon text-danger"></i>
            </div>
        </div>
    </div>

    {{-- Filter & Search Form --}}
    <div class="admin-card mb-4 p-3">
        <form method="GET" action="{{ route('admin.ludo.deposits.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-dark text-secondary border-secondary border-opacity-50">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary border-opacity-50" placeholder="Search by UTR, UID, Name, Phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <select name="status" class="form-select bg-dark text-light border-secondary border-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Only</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved Only</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected Only</option>
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="from" class="form-control bg-dark text-light border-secondary border-opacity-50" value="{{ request('from') }}" title="From Date">
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="to" class="form-control bg-dark text-light border-secondary border-opacity-50" value="{{ request('to') }}" title="To Date">
            </div>
            <div class="col-12 col-sm-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary fw-bold flex-grow-1">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'from', 'to']))
                    <a href="{{ route('admin.ludo.deposits.index') }}" class="btn btn-outline-secondary px-3">
                        Clear
                    </a>
                @endif
            </div>
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
                <tbody id="adminDepositsTableBody">
                    @forelse($deposits as $dep)
                        @php
                            $statusBadge = match($dep->status) {
                                'approved' => 'bg-success',
                                'rejected' => 'bg-danger',
                                default => 'bg-warning text-dark'
                            };
                        @endphp
                        <tr id="depositRow{{ $dep->id }}" class="deposit-table-row">
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
                                                    @php $receiptUrl = \App\Services\FileUploadService::url($dep->screenshot_path); @endphp
                                                    <img src="{{ $receiptUrl }}" alt="Receipt" class="img-fluid rounded border border-secondary shadow-sm mb-2" style="max-height: 55vh; object-fit: contain;">
                                                    <div class="small text-secondary">
                                                        UTR: <strong class="text-warning font-monospace">{{ $dep->utr_number }}</strong> &bull; Amount: <strong class="text-success">₹{{ number_format($dep->amount) }}</strong>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-secondary border-opacity-50 justify-content-between">
                                                    <a href="{{ $receiptUrl }}" target="_blank" class="btn btn-sm btn-outline-info">
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('adminDepositsTableBody');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    let isFetchingFeed = false;

    // Fetch live feed of deposits and update table + stats
    async function refreshDepositsLiveFeed() {
        if (document.hidden || isFetchingFeed) return;
        isFetchingFeed = true;

        try {
            const url = new URL("{{ route('admin.ludo.deposits.liveFeed') }}", window.location.origin);
            const curParams = new URLSearchParams(window.location.search);
            if (curParams.has('status')) url.searchParams.set('status', curParams.get('status'));

            const res = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (res.ok) {
                const data = await res.json();
                if (data && data.success) {
                    // 1. Update Stat Numbers
                    if (data.counts) {
                        const elP = document.getElementById('statPendingCount');
                        if (elP) elP.textContent = Number(data.counts.pending || 0).toLocaleString();
                        const elA = document.getElementById('statApprovedCount');
                        if (elA) elA.textContent = Number(data.counts.approved || 0).toLocaleString();
                        const elR = document.getElementById('statRejectedCount');
                        if (elR) elR.textContent = Number(data.counts.rejected || 0).toLocaleString();
                        const elT = document.getElementById('statTotalApprovedAmount');
                        if (elT) elT.textContent = '₹' + Number(data.counts.total_approved_amount || 0).toLocaleString();
                    }

                    // 2. Check for new deposits not yet in the table
                    if (Array.isArray(data.deposits) && tableBody) {
                        data.deposits.forEach(function(dep) {
                            let existingRow = document.getElementById('depositRow' + dep.id);
                            if (!existingRow) {
                                // Remove empty placeholder if present
                                const emptyRow = tableBody.querySelector('tr td[colspan]');
                                if (emptyRow) emptyRow.closest('tr').remove();

                                // Prepend new row
                                const badgeClass = dep.status === 'approved' ? 'bg-success' : (dep.status === 'rejected' ? 'bg-danger' : 'bg-warning text-dark');
                                const rowHtml = `
                                    <tr id="depositRow${dep.id}" class="deposit-table-row table-warning" style="transition: background-color 2.5s;">
                                        <td><span class="font-monospace text-info fw-bold">${dep.deposit_uid}</span></td>
                                        <td>
                                            <div class="fw-bold text-white">${dep.user_name}</div>
                                            <div class="small text-secondary">${dep.user_phone} &bull; UID: ${dep.user_uid}</div>
                                        </td>
                                        <td><span class="fw-bold text-success fs-6">₹${Number(dep.amount).toLocaleString()}</span></td>
                                        <td>
                                            <span class="font-monospace fw-bold text-warning bg-dark px-2 py-1 rounded border border-secondary border-opacity-50">
                                                ${dep.utr_number}
                                            </span>
                                        </td>
                                        <td>
                                            ${dep.receipt_url ? `
                                                <a href="${dep.receipt_url}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-2 py-0" style="font-size: 11px;">
                                                    <i class="fa-solid fa-image me-1"></i> View Receipt
                                                </a>
                                            ` : '<span class="text-secondary small">None</span>'}
                                        </td>
                                        <td>
                                            <span class="badge ${badgeClass} text-uppercase" style="font-size: 10px;">
                                                ${dep.status}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small text-light">${dep.created_at_formatted}</div>
                                            <div class="small text-secondary" style="font-size: 10.5px;">${dep.created_at_human}</div>
                                        </td>
                                        <td class="text-end">
                                            ${dep.status === 'pending' ? `
                                                <div class="d-inline-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold btn-ajax-approve" data-id="${dep.id}" style="font-size: 11px;">
                                                        <i class="fa-solid fa-check me-1"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 btn-ajax-reject-prompt" data-id="${dep.id}" style="font-size: 11px;">
                                                        Reject
                                                    </button>
                                                </div>
                                            ` : `
                                                <span class="small ${dep.status === 'approved' ? 'text-success' : 'text-danger'}">
                                                    ${dep.status === 'approved' ? '<i class="fa-solid fa-check-double me-1"></i> Processed' : '<i class="fa-solid fa-ban me-1"></i> Rejected'}
                                                </span>
                                            `}
                                        </td>
                                    </tr>
                                `;
                                tableBody.insertAdjacentHTML('afterbegin', rowHtml);
                                const newRow = document.getElementById('depositRow' + dep.id);
                                setTimeout(() => {
                                    if (newRow) newRow.classList.remove('table-warning');
                                }, 3000);
                            } else {
                                // If status transitioned from pending to approved/rejected
                                const statusBadge = existingRow.querySelector('td:nth-child(6) .badge');
                                if (statusBadge && !statusBadge.textContent.toLowerCase().includes(dep.status)) {
                                    statusBadge.className = 'badge ' + (dep.status === 'approved' ? 'bg-success' : 'bg-danger') + ' text-uppercase';
                                    statusBadge.textContent = dep.status;
                                    const actionTd = existingRow.querySelector('td.text-end');
                                    if (actionTd) {
                                        actionTd.innerHTML = `<span class="small ${dep.status === 'approved' ? 'text-success' : 'text-danger'}"><i class="fa-solid ${dep.status === 'approved' ? 'fa-check-double' : 'fa-ban'} me-1"></i> Processed</span>`;
                                    }
                                }
                            }
                        });
                    }
                }
            }
        } catch (err) {
            console.debug('Deposits live feed error:', err);
        } finally {
            isFetchingFeed = false;
        }
    }

    // Listen to global deposit alerts
    window.addEventListener('admin:deposit-alert', function(e) {
        refreshDepositsLiveFeed();
    });

    // Recurring polling feed
    setInterval(refreshDepositsLiveFeed, 3500);

    // Event delegation for Approve and Reject buttons
    document.addEventListener('click', async function(e) {
        const approveBtn = e.target.closest('.btn-ajax-approve');
        if (approveBtn) {
            const depositId = approveBtn.getAttribute('data-id');
            if (!confirm('Approve this deposit and credit coins atomically to the player?')) return;

            approveBtn.disabled = true;
            approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Approving...';

            try {
                const res = await fetch(`/admin/ludo/deposits/${depositId}/approve`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.showAdminToast) {
                        window.showAdminToast('Deposit Approved', data.message, null, 'success', 'fa-solid fa-circle-check text-success');
                    }
                    refreshDepositsLiveFeed();
                } else {
                    alert(data.message || 'Failed to approve deposit.');
                    approveBtn.disabled = false;
                    approveBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Approve';
                }
            } catch (err) {
                alert('Network error while processing deposit approval.');
                approveBtn.disabled = false;
                approveBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Approve';
            }
        }

        const rejectPromptBtn = e.target.closest('.btn-ajax-reject-prompt');
        if (rejectPromptBtn) {
            const depositId = rejectPromptBtn.getAttribute('data-id');
            const reason = prompt('Please enter the reason for rejecting this deposit:');
            if (!reason || reason.trim().length < 4) {
                if (reason !== null) alert('Rejection reason must be at least 4 characters.');
                return;
            }

            rejectPromptBtn.disabled = true;
            try {
                const res = await fetch(`/admin/ludo/deposits/${depositId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ reason: reason.trim() })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (window.showAdminToast) {
                        window.showAdminToast('Deposit Rejected', data.message, null, 'warning', 'fa-solid fa-ban text-warning');
                    }
                    refreshDepositsLiveFeed();
                } else {
                    alert(data.message || 'Failed to reject deposit.');
                    rejectPromptBtn.disabled = false;
                }
            } catch (err) {
                alert('Network error while rejecting deposit.');
                rejectPromptBtn.disabled = false;
            }
        }
    });
});
</script>
@endpush
@endsection
