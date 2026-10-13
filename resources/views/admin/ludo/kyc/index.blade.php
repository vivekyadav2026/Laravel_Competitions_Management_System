@extends('layouts.admin')

@section('title', 'KYC Document Verifications — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'KYC Compliance & Verification Desk')

@section('content')
    {{-- KYC Summary Counters --}}
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Pending Review Queue</div>
                <div class="admin-stat-val text-warning">{{ number_format($summary['pending'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Verified (Approved)</div>
                <div class="admin-stat-val text-success">{{ number_format($summary['approved'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-4">
            <div class="admin-stat-card">
                <div class="admin-stat-title">Rejected Submissions</div>
                <div class="admin-stat-val text-danger">{{ number_format($summary['rejected'] ?? 0) }}</div>
            </div>
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
            <div class="col-6 col-md-3">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Review</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Verified (Approved)</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
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
                <a href="{{ route('admin.ludo.kyc.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
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
                                <div class="d-flex flex-column gap-1">
                                    @if($sub->front_image_path)
                                        <a href="{{ route('ludo.kyc.download', [$sub->id, 'front']) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10.5px;">
                                            <i class="fa-solid fa-file-image me-1"></i> Front Copy
                                        </a>
                                    @endif
                                    @if($sub->back_image_path)
                                        <a href="{{ route('ludo.kyc.download', [$sub->id, 'back']) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10.5px;">
                                            <i class="fa-solid fa-file-image me-1"></i> Back Copy
                                        </a>
                                    @endif
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
                                {{ $sub->created_at->format('d M, h:i A') }}
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
@endsection
