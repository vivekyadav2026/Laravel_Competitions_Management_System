@extends('layouts.player')

@section('title', 'Royal Ludo — KYC Verification')

@section('content')
    <div class="ludo-create-battle-card mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <i class="fa-solid fa-id-card text-warning fs-5"></i>
            <h5 class="fw-bold text-white mb-0 fs-6">KYC Document Verification</h5>
        </div>
        <p class="small text-secondary mb-3">As per regulatory compliance, please verify your identity to enable high-volume withdrawals directly to your bank account.</p>

        <form action="#" method="POST" onsubmit="event.preventDefault(); showLudoToast('KYC submission received! Verification takes up to 24 hours.');">
            <div class="mb-3">
                <label class="form-label text-light small fw-bold">Select Document Type</label>
                <select class="form-select bg-dark text-light border-secondary border-opacity-50 small rounded-3">
                    <option value="aadhaar">Aadhaar Card</option>
                    <option value="pan">PAN Card</option>
                    <option value="voter">Voter ID Card</option>
                    <option value="driving">Driving License</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label text-light small fw-bold">Document Number</label>
                <input type="text" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" placeholder="Enter document number (e.g. 1234 5678 9012)" required>
            </div>

            <div class="mb-3">
                <label class="form-label text-light small fw-bold">Upload Document Front Photo</label>
                <input type="file" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" accept="image/*">
            </div>

            <button type="submit" class="ludo-btn-invite" style="padding: 10px; font-size: 14px;">
                Submit for KYC Verification
            </button>
        </form>
    </div>
@endsection
