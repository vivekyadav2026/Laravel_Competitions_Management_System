@extends('layouts.admin')

@section('title', 'KYC Document Verifications — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'KYC Compliance & Verification Desk')

@section('content')
    {{-- KYC Summary Counters --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.ludo.kyc.index', ['status' => 'pending']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100 {{ request('status') === 'pending' ? 'border border-warning' : '' }}">
                    <div class="admin-stat-title">Pending Review Queue</div>
                    <div class="admin-stat-val text-warning">{{ number_format($summary['pending'] ?? 0) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">Awaiting compliance review</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.ludo.kyc.index', ['status' => 'approved']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100 {{ request('status') === 'approved' ? 'border border-success' : '' }}">
                    <div class="admin-stat-title">Verified (Approved)</div>
                    <div class="admin-stat-val text-success">{{ number_format($summary['approved'] ?? 0) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">Active verified accounts</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.ludo.kyc.index', ['status' => 'rejected']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100 {{ request('status') === 'rejected' ? 'border border-danger' : '' }}">
                    <div class="admin-stat-title">Rejected Submissions</div>
                    <div class="admin-stat-val text-danger">{{ number_format($summary['rejected'] ?? 0) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">Declined or invalid docs</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.ludo.users.index', ['kyc_status' => 'unverified']) }}" class="text-decoration-none">
                <div class="admin-stat-card h-100">
                    <div class="admin-stat-title">Unverified Players</div>
                    <div class="admin-stat-val text-secondary">{{ number_format($summary['unverified'] ?? 0) }}</div>
                    <div class="small text-secondary mt-1" style="font-size: 11px;">No KYC submitted yet &rarr;</div>
                </div>
            </a>
        </div>
    </div>

    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.kyc.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search by Document Number or Player..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Review</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Verified (Approved)</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-6 col-sm-6 col-md-3">
                <select name="document_type" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Document Types</option>
                    <option value="aadhaar" {{ request('document_type') === 'aadhaar' ? 'selected' : '' }}>Aadhaar Card</option>
                    <option value="pan" {{ request('document_type') === 'pan' ? 'selected' : '' }}>PAN Card</option>
                    <option value="voter_id" {{ request('document_type') === 'voter_id' ? 'selected' : '' }}>Voter ID</option>
                    <option value="driving_license" {{ request('document_type') === 'driving_license' ? 'selected' : '' }}>Driving License</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.kyc.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Reset</a>
            </div>
        </form>

        {{-- KYC Submissions Table --}}
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Player</th>
                        <th>Document Type</th>
                        <th>Document ID</th>
                        <th>Protected Documents</th>
                        <th>Status & Reviewer</th>
                        <th>Submitted</th>
                        <th>Compliance Review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($submissions as $sub)
                        <tr>
                            <td>
                                <a href="{{ route('admin.ludo.users.show', $sub->user_id) }}" class="text-white small fw-bold text-decoration-none d-block">
                                    {{ $sub->user->name ?? 'Player #' . $sub->user_id }}
                                </a>
                                <div class="text-secondary small">{{ $sub->user->email ?? 'No email' }}</div>
                                <span class="badge bg-dark border border-secondary text-info" style="font-size: 9px;">
                                    UID: {{ $sub->user->player_uid ?? $sub->user_id }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-warning text-uppercase" style="font-size: 10px;">
                                    {{ str_replace('_', ' ', $sub->document_type) }}
                                </span>
                            </td>
                            <td>
                                <span class="font-monospace text-light small fw-bold">{{ $sub->document_number }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1" style="min-width: 140px;">
                                    <button type="button" 
                                            class="btn btn-sm btn-info text-dark rounded-pill py-1 px-2 fw-bold d-flex align-items-center justify-content-center gap-1 btn-open-kyc-modal"
                                            style="font-size: 11px;"
                                            data-id="{{ $sub->id }}"
                                            data-user-name="{{ $sub->user->name ?? 'Player #' . $sub->user_id }}"
                                            data-user-uid="{{ $sub->user->player_uid ?? $sub->user_id }}"
                                            data-user-email="{{ $sub->user->email ?? 'No email' }}"
                                            data-doc-type="{{ strtoupper(str_replace('_', ' ', $sub->document_type)) }}"
                                            data-doc-number="{{ $sub->document_number }}"
                                            data-status="{{ $sub->status }}"
                                            data-reason="{{ $sub->admin_reason ?? '' }}"
                                            data-submitted="{{ $sub->created_at ? $sub->created_at->format('d M Y, h:i A') : '—' }}"
                                            data-front-view="{{ route('ludo.kyc.view', [$sub->id, 'front']) }}"
                                            data-front-dl="{{ route('ludo.kyc.download', [$sub->id, 'front']) }}"
                                            data-back-view="{{ $sub->back_image_path ? route('ludo.kyc.view', [$sub->id, 'back']) : '' }}"
                                            data-back-dl="{{ $sub->back_image_path ? route('ludo.kyc.download', [$sub->id, 'back']) : '' }}"
                                            data-review-url="{{ route('ludo.admin.kyc.review', $sub->id) }}">
                                        <i class="fa-solid fa-eye"></i> View Document
                                    </button>

                                    <div class="d-flex gap-1 justify-content-center mt-1">
                                        @if($sub->front_image_path)
                                            <a href="{{ route('ludo.kyc.view', [$sub->id, 'front']) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10px;" title="Open Front copy in new browser tab">
                                                <i class="fa-solid fa-external-link me-1"></i> Front
                                            </a>
                                        @endif
                                        @if($sub->back_image_path)
                                            <a href="{{ route('ludo.kyc.view', [$sub->id, 'back']) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10px;" title="Open Back copy in new browser tab">
                                                <i class="fa-solid fa-external-link me-1"></i> Back
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $sub->status === 'approved' ? 'bg-success' : ($sub->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                    {{ $sub->status }}
                                </span>
                                @if($sub->admin_reason)
                                    <div class="text-light small mt-1" style="font-size: 10.5px; max-width: 180px;">
                                        {{ Str::limit($sub->admin_reason, 55) }}
                                    </div>
                                @endif
                                @if($sub->reviewer)
                                    <div class="text-secondary" style="font-size: 10px;">
                                        By {{ $sub->reviewer->name }}
                                        {{ $sub->reviewed_at ? '(' . $sub->reviewed_at->format('d M, h:i A') . ')' : '' }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-secondary small">
                                {{ $sub->created_at ? $sub->created_at->format('d M, h:i A') : '—' }}
                            </td>
                            <td>
                                @if($sub->status !== 'approved')
                                    <form action="{{ route('ludo.admin.kyc.review', $sub->id) }}" method="POST" class="d-flex flex-column gap-1" style="min-width: 170px;">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary" style="font-size: 10.5px;" required>
                                            <option value="approved">Approve & Verify</option>
                                            <option value="rejected">Reject Submission</option>
                                        </select>
                                        <input type="text" name="admin_reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Reason/notes..." required minlength="5" style="font-size: 10.5px;">
                                        <button type="submit" class="btn btn-sm btn-info rounded-pill py-0 px-2 fw-bold" style="font-size: 10.5px;" onclick="return confirm('Confirm KYC review verdict?')">
                                            Execute Review
                                        </button>
                                    </form>
                                @else
                                    <span class="text-success small fw-semibold">
                                        <i class="fa-solid fa-check-double me-1"></i> Verified
                                    </span>
                                    <div class="mt-1">
                                        <a href="{{ route('admin.ludo.users.show', $sub->user_id) }}" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 10px;">
                                            User Dossier &rarr;
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No KYC document submissions found matching the criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $submissions->links('pagination::bootstrap-5') }}</div>
    </div>

    {{-- KYC Document Inspection & Preview Modal --}}
    <div class="modal fade" id="kycDocPreviewModal" tabindex="-1" aria-labelledby="kycDocPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-secondary shadow-lg" style="background: #0b1224; border-radius: 16px; overflow: hidden;">
                <div class="modal-header border-secondary border-opacity-25 py-2 px-3" style="background: rgba(14, 28, 64, 0.8);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-id-card text-warning fs-5"></i>
                        <div>
                            <h6 class="modal-title fw-bold text-white mb-0" id="kycDocPreviewModalLabel">
                                KYC Document Inspection — <span id="kycModalUserName" class="text-info">Player</span>
                            </h6>
                            <div class="small text-secondary" style="font-size: 11px;">
                                UID: <span id="kycModalUserUid" class="text-light fw-semibold"></span> &bull; 
                                Type: <span id="kycModalDocType" class="badge bg-dark border border-secondary text-warning"></span> &bull; 
                                Number: <span id="kycModalDocNumber" class="text-white font-monospace fw-bold"></span> &bull; 
                                Status: <span id="kycModalStatusBadge" class="badge"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    {{-- Side Navigation Tabs & Controls --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary border-opacity-25 flex-wrap gap-2">
                        <ul class="nav nav-pills gap-1" id="kycDocSideTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active btn-sm rounded-pill py-1 px-3 text-white fw-semibold" id="tab-front-btn" type="button" onclick="switchKycSide('front')">
                                    <i class="fa-solid fa-image me-1"></i> Front Side
                                </button>
                            </li>
                            <li class="nav-item" role="presentation" id="tab-back-wrapper">
                                <button class="nav-link btn-sm rounded-pill py-1 px-3 text-white fw-semibold" id="tab-back-btn" type="button" onclick="switchKycSide('back')">
                                    <i class="fa-solid fa-image me-1"></i> Back Side
                                </button>
                            </li>
                            <li class="nav-item" role="presentation" id="tab-dual-wrapper">
                                <button class="nav-link btn-sm rounded-pill py-1 px-3 text-white fw-semibold" id="tab-dual-btn" type="button" onclick="switchKycSide('dual')">
                                    <i class="fa-solid fa-table-columns me-1"></i> Both Sides
                                </button>
                            </li>
                        </ul>

                        {{-- Zoom & View Controls --}}
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary text-light rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" onclick="zoomKycImage(0.2)" title="Zoom In">
                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-light rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" onclick="zoomKycImage(-0.2)" title="Zoom Out">
                                <i class="fa-solid fa-magnifying-glass-minus"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary text-light rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" onclick="resetKycZoom()" title="Reset Zoom">
                                <i class="fa-solid fa-rotate-left"></i>
                            </button>
                            <a href="#" target="_blank" id="kycModalOpenNewTab" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2 d-flex align-items-center gap-1" style="font-size: 11px;" title="Open in New Window">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Open High-Res
                            </a>
                        </div>
                    </div>

                    {{-- Single View (Front or Back) Container --}}
                    <div id="kycSingleViewContainer" class="p-2 text-center rounded-3 position-relative overflow-hidden" style="background: #040814; min-height: 240px; max-height: 55vh; border: 1px dashed rgba(255,255,255,0.15);">
                        <div class="d-flex align-items-center justify-content-center h-100 w-100 overflow-auto" id="kycImageScrollBox" style="max-height: 52vh;">
                            <img src="" id="kycMainPreviewImg" class="img-fluid rounded shadow" style="max-height: 50vh; object-fit: contain; transform-origin: center center; transition: transform 0.2s ease;" alt="KYC Document Preview" onerror="this.onerror=null; this.src='{{ asset('uploads/kyc_documents/sample_verified_front.png') }}';">
                        </div>
                        <div class="position-absolute bottom-0 start-0 m-2 px-2 py-1 rounded bg-dark bg-opacity-75 text-light" style="font-size: 10px;" id="kycCurrentSideIndicator">
                            Front Side Document
                        </div>
                    </div>

                    {{-- Dual View Container (Both Front & Back) --}}
                    <div id="kycDualViewContainer" class="row g-2 d-none">
                        <div class="col-12 col-sm-6">
                            <div class="p-2 text-center rounded-3" style="background: #040814; border: 1px dashed rgba(255,255,255,0.15);">
                                <div class="small text-secondary mb-1 fw-bold">Front Side</div>
                                <img src="" id="kycDualFrontImg" class="img-fluid rounded" style="max-height: 300px; object-fit: contain;" alt="Front Document" onerror="this.onerror=null; this.src='{{ asset('uploads/kyc_documents/sample_verified_front.png') }}';">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-2 text-center rounded-3" style="background: #040814; border: 1px dashed rgba(255,255,255,0.15);">
                                <div class="small text-secondary mb-1 fw-bold">Back Side</div>
                                <img src="" id="kycDualBackImg" class="img-fluid rounded" style="max-height: 300px; object-fit: contain;" alt="Back Document" onerror="this.onerror=null; this.src='{{ asset('uploads/kyc_documents/sample_verified_back.png') }}';">
                            </div>
                        </div>
                    </div>

                    {{-- Rejection / Admin notes if any --}}
                    <div id="kycModalReasonContainer" class="alert alert-danger py-2 px-3 small rounded-3 mt-3 mb-0 d-none">
                        <strong>Previous Rejection Notes:</strong> <span id="kycModalReasonText"></span>
                    </div>

                    {{-- Modal In-Place Compliance Review Form --}}
                    <div id="kycModalReviewBox" class="mt-3 p-3 rounded-3 d-none" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08);">
                        <h6 class="text-warning small fw-bold mb-2">
                            <i class="fa-solid fa-gavel me-1"></i> Quick Compliance Review Verdict
                        </h6>
                        <form id="kycModalReviewForm" method="POST" action="">
                            @csrf
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                        <option value="approved">Approve & Verify Player</option>
                                        <option value="rejected">Reject Submission</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-5">
                                    <input type="text" name="admin_reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Review notes (e.g., Aadhaar verified successfully)..." required minlength="5">
                                </div>
                                <div class="col-12 col-md-3">
                                    <button type="submit" class="btn btn-sm btn-info w-100 rounded-pill fw-bold" onclick="return confirm('Confirm KYC review verdict?')">
                                        Execute Review
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal-footer border-secondary border-opacity-25 py-2 px-3 justify-content-between" style="background: rgba(14, 28, 64, 0.5);">
                    <div class="d-flex gap-2">
                        <a href="#" id="kycModalFrontDlBtn" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size: 11px;">
                            <i class="fa-solid fa-download me-1"></i> Download Front
                        </a>
                        <a href="#" id="kycModalBackDlBtn" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2 d-none" style="font-size: 11px;">
                            <i class="fa-solid fa-download me-1"></i> Download Back
                        </a>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let currentKycZoom = 1.0;
        let currentKycData = null;
        let activeKycSide = 'front';
        let kycModalInstance = null;

        document.addEventListener('DOMContentLoaded', function () {
            const previewModalEl = document.getElementById('kycDocPreviewModal');
            if (previewModalEl && typeof bootstrap !== 'undefined') {
                kycModalInstance = new bootstrap.Modal(previewModalEl);
            }

            document.querySelectorAll('.btn-open-kyc-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    currentKycData = {
                        id: this.getAttribute('data-id'),
                        name: this.getAttribute('data-user-name'),
                        uid: this.getAttribute('data-user-uid'),
                        docType: this.getAttribute('data-doc-type'),
                        docNumber: this.getAttribute('data-doc-number'),
                        status: this.getAttribute('data-status'),
                        reason: this.getAttribute('data-reason'),
                        submitted: this.getAttribute('data-submitted'),
                        frontView: this.getAttribute('data-front-view'),
                        frontDl: this.getAttribute('data-front-dl'),
                        backView: this.getAttribute('data-back-view'),
                        backDl: this.getAttribute('data-back-dl'),
                        reviewUrl: this.getAttribute('data-review-url')
                    };

                    // Populate modal headers and labels
                    document.getElementById('kycModalUserName').textContent = currentKycData.name;
                    document.getElementById('kycModalUserUid').textContent = currentKycData.uid;
                    document.getElementById('kycModalDocType').textContent = currentKycData.docType;
                    document.getElementById('kycModalDocNumber').textContent = currentKycData.docNumber;

                    // Status Badge
                    const statusBadge = document.getElementById('kycModalStatusBadge');
                    statusBadge.textContent = currentKycData.status.toUpperCase();
                    statusBadge.className = 'badge ' + (
                        currentKycData.status === 'approved' ? 'bg-success' :
                        (currentKycData.status === 'pending' ? 'bg-warning text-dark' : 'bg-danger')
                    );

                    // Rejection Notes
                    const reasonBox = document.getElementById('kycModalReasonContainer');
                    if (currentKycData.reason && currentKycData.status === 'rejected') {
                        document.getElementById('kycModalReasonText').textContent = currentKycData.reason;
                        reasonBox.classList.remove('d-none');
                    } else {
                        reasonBox.classList.add('d-none');
                    }

                    // Review Form
                    const reviewBox = document.getElementById('kycModalReviewBox');
                    const reviewForm = document.getElementById('kycModalReviewForm');
                    if (currentKycData.status !== 'approved' && currentKycData.reviewUrl) {
                        reviewForm.action = currentKycData.reviewUrl;
                        reviewBox.classList.remove('d-none');
                    } else {
                        reviewBox.classList.add('d-none');
                    }

                    // Download Buttons & Tabs
                    document.getElementById('kycModalFrontDlBtn').href = currentKycData.frontDl;
                    const backDlBtn = document.getElementById('kycModalBackDlBtn');
                    const backTabWrapper = document.getElementById('tab-back-wrapper');
                    const dualTabWrapper = document.getElementById('tab-dual-wrapper');

                    if (currentKycData.backView && currentKycData.backView !== '') {
                        backDlBtn.href = currentKycData.backDl;
                        backDlBtn.classList.remove('d-none');
                        backTabWrapper.classList.remove('d-none');
                        dualTabWrapper.classList.remove('d-none');
                    } else {
                        backDlBtn.classList.add('d-none');
                        backTabWrapper.classList.add('d-none');
                        dualTabWrapper.classList.add('d-none');
                    }

                    // Reset zoom and show front side by default
                    resetKycZoom();
                    switchKycSide('front');

                    if (kycModalInstance) {
                        kycModalInstance.show();
                    }
                });
            });
        });

        function switchKycSide(side) {
            if (!currentKycData) return;
            activeKycSide = side;

            const singleContainer = document.getElementById('kycSingleViewContainer');
            const dualContainer = document.getElementById('kycDualViewContainer');
            const mainImg = document.getElementById('kycMainPreviewImg');
            const openNewTab = document.getElementById('kycModalOpenNewTab');
            const indicator = document.getElementById('kycCurrentSideIndicator');

            // Update active tab buttons
            document.getElementById('tab-front-btn')?.classList.toggle('active', side === 'front');
            document.getElementById('tab-back-btn')?.classList.toggle('active', side === 'back');
            document.getElementById('tab-dual-btn')?.classList.toggle('active', side === 'dual');

            if (side === 'dual') {
                singleContainer.classList.add('d-none');
                dualContainer.classList.remove('d-none');
                document.getElementById('kycDualFrontImg').src = currentKycData.frontView;
                document.getElementById('kycDualBackImg').src = currentKycData.backView;
                openNewTab.href = currentKycData.frontView;
            } else {
                dualContainer.classList.add('d-none');
                singleContainer.classList.remove('d-none');

                const viewUrl = side === 'back' ? currentKycData.backView : currentKycData.frontView;
                mainImg.src = viewUrl;
                openNewTab.href = viewUrl;
                indicator.textContent = side === 'back' ? 'Back Side Document' : 'Front Side Document';
            }
        }

        function zoomKycImage(delta) {
            currentKycZoom = Math.max(0.5, Math.min(3.0, currentKycZoom + delta));
            const img = document.getElementById('kycMainPreviewImg');
            if (img) {
                img.style.transform = `scale(${currentKycZoom})`;
            }
        }

        function resetKycZoom() {
            currentKycZoom = 1.0;
            const img = document.getElementById('kycMainPreviewImg');
            if (img) {
                img.style.transform = 'scale(1)';
            }
        }
    </script>
    @endpush
@endsection
