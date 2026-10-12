@extends('layouts.player')

@section('title', 'Royal Ludo — My Profile')

@section('content')
    {{-- Top Profile Header Card --}}
    <div class="ludo-refer-container py-4 mb-3 text-center position-relative">
        <div class="position-relative d-inline-block mb-2">
            @if(!empty($user->avatar))
                <img src="{{ asset($user->avatar) }}" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; border: 2.5px solid var(--ludo-cyan); object-fit: cover; background: #081226;">
            @else
                <img src="{{ asset('assets/ludo/nav-profile.svg') }}" alt="Profile" style="width: 80px; height: 80px; border-radius: 50%; border: 2.5px solid var(--ludo-cyan); padding: 6px; background: #081226;">
            @endif

            @if($user->is_verified)
                <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-success border border-dark" title="Verified Player">
                    <i class="fa-solid fa-check small"></i>
                </span>
            @endif
        </div>

        <h4 class="fw-bold text-white mb-1 d-flex align-items-center justify-content-center gap-2">
            <span>{{ $user->name ?? 'Royal Player' }}</span>
            @if($isProGamer)
                <span class="badge bg-warning text-dark border border-warning" style="font-size: 10px;" title="Legitimately earned Pro Gamer title">
                    🏆 PRO GAMER
                </span>
            @endif
        </h4>

        <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
            <span class="text-info small fw-bold">UID: {{ $user->player_uid ?? 'LUDO' . str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
            <span class="badge {{ $user->kyc_status === 'verified' ? 'bg-success' : ($user->kyc_status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}" style="font-size: 9.5px; text-transform: uppercase;">
                KYC: {{ $user->kyc_status ?? 'UNVERIFIED' }}
            </span>
        </div>

        {{-- 4 Battle Statistics Counters --}}
        <div class="row g-2 mt-2 pt-3 border-top border-secondary border-opacity-25 text-center">
            <div class="col-3">
                <div class="small text-secondary" style="font-size: 9.5px;">BATTLES</div>
                <div class="fw-bold text-white fs-6">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="col-3">
                <div class="small text-secondary" style="font-size: 9.5px;">WINS</div>
                <div class="fw-bold text-success fs-6">{{ $stats['won'] ?? 0 }}</div>
            </div>
            <div class="col-3">
                <div class="small text-secondary" style="font-size: 9.5px;">LOSSES</div>
                <div class="fw-bold text-danger fs-6">{{ $stats['lost'] ?? 0 }}</div>
            </div>
            <div class="col-3">
                <div class="small text-secondary" style="font-size: 9.5px;">WIN RATE</div>
                <div class="fw-bold text-warning fs-6">{{ $stats['win_rate'] ?? 0 }}%</div>
            </div>
        </div>

        <div class="mt-2 pt-2 border-top border-secondary border-opacity-25">
            <span class="text-secondary small" style="font-size: 11px;">Virtual Points Won: </span>
            <span class="text-success fw-bold">🪙 {{ number_format($stats['points_earned'] ?? 0) }} Points</span>
        </div>
    </div>

    {{-- Edit Profile Accordion / Card --}}
    <div class="ludo-create-battle-card mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-white mb-0 fs-6">
                <i class="fa-solid fa-user-pen text-info me-2"></i> Edit Profile & Privacy
            </h5>
            <button class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#editProfileCollapse">
                Toggle <i class="fa-solid fa-chevron-down ms-1"></i>
            </button>
        </div>

        <div class="collapse show" id="editProfileCollapse">
            <form action="{{ route('ludo.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-secondary small mb-1">Display Name</label>
                    <input type="text" name="name" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-3 small" value="{{ old('name', $user->name) }}" required maxlength="50">
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary small mb-1">Avatar Image (Max 2MB)</label>
                    <input type="file" name="avatar" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-3 small" accept="image/*">
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary small mb-1">Phone Number</label>
                    <input type="text" name="phone" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-3 small" value="{{ old('phone', $user->phone) }}" placeholder="+91 9876543210">
                </div>

                {{-- Privacy Controls --}}
                <div class="p-2 rounded-3 mb-3" style="background: rgba(14, 42, 92, 0.5); border: 1px solid #1d4ed8;">
                    <span class="fw-bold text-info small d-block mb-2"><i class="fa-solid fa-shield-halved me-1"></i> Privacy Preferences</span>
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="hide_email" id="hideEmailSwitch" value="1" {{ old('hide_email', $user->hide_email) ? 'checked' : '' }}>
                        <label class="form-check-label text-white small" for="hideEmailSwitch" style="font-size: 11px;">Hide Email from opponents</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="hide_phone" id="hidePhoneSwitch" value="1" {{ old('hide_phone', $user->hide_phone) ? 'checked' : '' }}>
                        <label class="form-check-label text-white small" for="hidePhoneSwitch" style="font-size: 11px;">Hide Phone from opponents</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-info w-100 rounded-3 text-dark fw-bold py-2">
                    Save Profile Changes
                </button>
            </form>
        </div>
    </div>

    {{-- Change Password Card --}}
    <div class="ludo-create-battle-card mb-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-white mb-0 fs-6">
                <i class="fa-solid fa-key text-warning me-2"></i> Change Password
            </h5>
            <button class="btn btn-sm btn-outline-warning rounded-pill py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#changePasswordCollapse">
                Toggle <i class="fa-solid fa-chevron-down ms-1"></i>
            </button>
        </div>

        <div class="collapse" id="changePasswordCollapse">
            <form action="{{ route('ludo.profile.password') }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label text-secondary small mb-1">Current Password</label>
                    <input type="password" name="current_password" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-3 small" required>
                </div>
                <div class="mb-2">
                    <label class="form-label text-secondary small mb-1">New Password (Min 8 chars)</label>
                    <input type="password" name="password" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-3 small" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small mb-1">Confirm New Password</label>
                    <input type="password" name="password_confirmation" class="form-control bg-dark text-white border-secondary border-opacity-50 rounded-3 small" required minlength="8">
                </div>
                <button type="submit" class="btn btn-outline-warning w-100 rounded-3 fw-bold py-2">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    {{-- Account & Verification Links --}}
    <div class="d-flex flex-column gap-2 mb-4">
        <a href="{{ url('/kyc') }}" class="ludo-support-item py-3">
            <i class="fa-solid fa-id-card fs-4 text-warning"></i>
            <div class="flex-grow-1">
                <div class="fw-bold text-white small">KYC Verification Center</div>
                <div class="text-secondary" style="font-size: 10px;">Aadhaar & PAN verification documents</div>
            </div>
            <span class="badge {{ $user->kyc_status === 'verified' ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size: 10px; text-transform: uppercase;">
                {{ $user->kyc_status ?? 'UNVERIFIED' }}
            </span>
        </a>

        <a href="{{ url('/wallet') }}" class="ludo-support-item py-3">
            <i class="fa-solid fa-coins fs-4 text-warning"></i>
            <div class="flex-grow-1">
                <div class="fw-bold text-white small">My Wallet & Balance</div>
                <div class="text-secondary" style="font-size: 10px;">Deposit points, winnings and payouts</div>
            </div>
            <span class="text-info fw-bold small">🪙 {{ number_format($coins) }}</span>
        </a>
    </div>

    {{-- Logout Button --}}
    @auth
        <a href="{{ url('/logout') }}" class="btn btn-outline-danger w-100 rounded-3 fw-bold py-2 mb-3">
            <i class="fa-solid fa-right-from-bracket me-1"></i> Log Out
        </a>
    @endauth
@endsection
