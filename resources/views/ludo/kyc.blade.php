@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — KYC Verification')

@section('content')
    <div class="ludo-create-battle-card mb-4" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #2563eb; border-radius: 14px; padding: 18px;">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-id-card text-warning fs-5"></i>
                <h5 class="fw-bold text-white mb-0" style="font-size: 15px;">KYC Identity Verification</h5>
            </div>
            @php
                $status = $user->kyc_status ?? 'unverified';
            @endphp
            <span class="badge {{ $status === 'verified' ? 'bg-success' : ($status === 'pending' ? 'bg-warning text-dark' : ($status === 'rejected' ? 'bg-danger' : 'bg-secondary')) }}" style="font-size: 10px; text-transform: uppercase;">
                {{ $status }}
            </span>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div class="alert alert-success bg-success bg-opacity-25 text-success border border-success border-opacity-50 small rounded-3 mb-3 py-2 px-3">
                <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 small rounded-3 mb-3 py-2 px-3">
                <i class="fa-solid fa-circle-exclamation me-1"></i> {{ session('error') }}
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 small rounded-3 mb-3 py-2 px-3">
                @foreach($errors->all() as $err)
                    <div>• {{ $err }}</div>
                @endforeach
            </div>
        @endif

        {{-- Policy Notice --}}
        <div class="p-3 rounded-3 mb-3" style="background: rgba(8, 18, 38, 0.7); border: 1px solid rgba(56, 189, 248, 0.2);">
            <div class="d-flex align-items-center gap-2 text-info small fw-bold mb-1">
                <i class="fa-solid fa-shield-halved"></i> Verification Policy
            </div>
            <div class="text-secondary small" style="font-size: 11.5px; line-height: 1.4;">
                @if(config('ludo.kyc.required_for_battles'))
                    <span class="text-warning fw-semibold">Mandatory:</span> Identification is required before participating in tournament challenges.
                @else
                    <span class="text-success fw-semibold">Optional for standard battles:</span> Identification is only required for high-tier contests and identity integrity. Documents are stored encrypted in private storage and reviewed manually by administrative staff.
                @endif
            </div>
        </div>

        @if($status === 'verified')
            {{-- Verified State --}}
            <div class="p-4 rounded-3 text-center my-3" style="background: rgba(34, 197, 94, 0.1); border: 1px solid #22c55e;">
                <i class="fa-solid fa-circle-check text-success fs-1 mb-2"></i>
                <h5 class="text-white fw-bold">Identity Verified</h5>
                <p class="text-secondary small mb-2">Your profile is fully verified. You enjoy unrestricted access to all platform features.</p>
                @if($submission)
                    <div class="text-secondary small mt-2">
                        Document: <strong class="text-light">{{ strtoupper(str_replace('_', ' ', $submission->document_type)) }}</strong> ({{ substr($submission->document_number, 0, 4) }}****)
                    </div>
                @endif
            </div>

        @elseif($status === 'pending')
            {{-- Pending Review State --}}
            <div class="p-4 rounded-3 text-center my-3" style="background: rgba(234, 179, 8, 0.1); border: 1px solid #eab308;">
                <i class="fa-solid fa-hourglass-half text-warning fs-1 mb-2"></i>
                <h5 class="text-white fw-bold">Verification Under Review</h5>
                <p class="text-secondary small mb-2">Your submitted identification is currently being inspected by compliance officers. Turnaround time is usually within 24 hours.</p>
                @if($submission)
                    <div class="text-secondary small mt-2">
                        Submitted: <strong class="text-light">{{ $submission->created_at->format('d M Y, h:i A') }}</strong> | 
                        Type: <strong class="text-light">{{ strtoupper(str_replace('_', ' ', $submission->document_type)) }}</strong>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('ludo.kyc.download', [$submission->id, 'front']) }}" class="btn btn-sm btn-outline-info rounded-pill py-1 px-3" style="font-size: 11px;">
                            <i class="fa-solid fa-file me-1"></i> View Submitted Front Copy
                        </a>
                    </div>
                @endif
            </div>

        @else
            {{-- Unverified or Rejected Form --}}
            @if($status === 'rejected' && $submission && $submission->admin_reason)
                <div class="alert alert-danger bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 small rounded-3 mb-3 py-2 px-3">
                    <strong>Previous Submission Rejected:</strong> {{ $submission->admin_reason }}
                    <div class="mt-1">Please re-upload a clear, non-blurry copy of your government ID below.</div>
                </div>
            @endif

            <form action="{{ route('ludo.kyc.submit') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Select Government Document Type <span class="text-danger">*</span></label>
                    <select name="document_type" class="form-select bg-dark text-light border-secondary border-opacity-50 small rounded-3" required>
                        <option value="aadhaar">Aadhaar Card (UIDAI)</option>
                        <option value="pan">PAN Card (Income Tax)</option>
                        <option value="voter_id">Voter ID Card (Election Commission)</option>
                        <option value="driving_license">Driving License</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Document Identification Number <span class="text-danger">*</span></label>
                    <input type="text" name="document_number" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" placeholder="e.g. 1234 5678 9012 or ABCDE1234F" required maxlength="50">
                    <div class="text-secondary" style="font-size: 10.5px; margin-top: 2px;">Only digits and uppercase alphanumeric symbols accepted.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Front Photo / Scanned Copy <span class="text-danger">*</span></label>
                    <input type="file" name="front_image" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                    <div class="text-secondary" style="font-size: 10.5px; margin-top: 2px;">Ensure full name, document number, and date of birth are crisp and legible (Max 5MB).</div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Back Photo (Optional for PAN / Driving License)</label>
                    <input type="file" name="back_image" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf">
                </div>

                <button type="submit" class="ludo-btn-invite w-100" style="padding: 11px; font-size: 13.5px; font-weight: bold;">
                    <i class="fa-solid fa-lock me-1"></i> Submit for KYC Verification
                </button>
            </form>
        @endif
    </div>
@endsection
