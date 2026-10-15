@extends('layouts.admin')

@section('title', 'System Settings — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Platform System Configurations')

@section('content')
    <div class="admin-card mb-4" style="max-width: 960px;">
        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span><i class="fa-solid fa-sliders text-warning me-2"></i> Platform & Payment Configuration Desk</span>
            <a href="{{ route('admin.ludo.deposits.index') }}" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 fw-bold">
                <i class="fa-solid fa-receipt me-1"></i> View Deposit Desk
            </a>
        </h5>

        <form action="{{ route('admin.ludo.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- SECTION 1: CORE BRAND & OPERATIONAL SETTINGS --}}
            <div class="mb-4 p-3 rounded-3" style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08);">
                <h6 class="text-warning fw-bold text-uppercase mb-3" style="letter-spacing: 0.5px;">
                    <i class="fa-solid fa-shield-halved me-1"></i> 1. Core Platform & Brand Parameters
                </h6>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Platform Brand Title <span class="text-danger">*</span></label>
                        <input type="text" name="platform_name" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['platform_name'] }}" required maxlength="100">
                        <small class="text-secondary">Official name displayed in player browser titlebars and headers.</small>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">
                            <i class="fa-solid fa-gift text-warning me-1"></i> Joining Bonus / Welcome Bonus (नए प्लेयर को जॉइनिंग पॉइंट्स) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-secondary border-secondary text-warning fw-bold">🪙 Points</span>
                            <input type="number" name="welcome_bonus_points" class="form-control bg-dark text-light border-secondary small fw-bold" value="{{ $settings['welcome_bonus_points'] ?? 100 }}" min="0" max="10000" required>
                        </div>
                        <small class="text-secondary">नए रजिस्टर होने वाले हर खिलाड़ी को यह जॉइनिंग बोनस तुरंत क्रेडिट किया जाएगा। (Points credited instantly on registration).</small>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">Match Result Submission Window (Min) <span class="text-danger">*</span></label>
                        <input type="number" name="submission_window" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['submission_window'] }}" min="5" max="120" required>
                        <small class="text-secondary">Submissions after this window are flagged for admin review.</small>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">Referral Commission Label <span class="text-danger">*</span></label>
                        <input type="text" name="referral_commission" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['referral_commission'] }}" required>
                        <small class="text-secondary">Display commission label on Refer & Earn.</small>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">Referral Signup Bonus (Points)</label>
                        <input type="number" name="referral_reward_points" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['referral_reward_points'] ?? 50 }}" min="0" max="5000">
                        <small class="text-secondary">Virtual points awarded per eligible referral.</small>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">Minimum Battle Entry Points <span class="text-danger">*</span></label>
                        <input type="number" name="minimum_battle_amount" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['minimum_battle_amount'] ?? '50' }}" min="10" max="1000" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">KYC Mandatory for Standard Battles</label>
                        <select name="kyc_mandatory" class="form-select bg-dark text-light border-secondary small">
                            <option value="off" {{ $settings['kyc_mandatory'] === 'off' ? 'selected' : '' }}>Off (Optional for basic battles)</option>
                            <option value="on" {{ $settings['kyc_mandatory'] === 'on' ? 'selected' : '' }}>On (Enforce KYC before match entry)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">Referral Program Toggle</label>
                        <select name="referral_enabled" class="form-select bg-dark text-light border-secondary small">
                            <option value="on" {{ ($settings['referral_enabled'] ?? 'on') === 'on' ? 'selected' : '' }}>On (Enabled)</option>
                            <option value="off" {{ ($settings['referral_enabled'] ?? 'on') === 'off' ? 'selected' : '' }}>Off (Disabled)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Support Contact Email <span class="text-danger">*</span></label>
                        <input type="email" name="support_email" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['support_email'] }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Platform Maintenance Mode</label>
                        <select name="maintenance_mode" class="form-select bg-dark text-light border-secondary small">
                            <option value="off" {{ $settings['maintenance_mode'] === 'off' ? 'selected' : '' }}>Off (Normal live operations)</option>
                            <option value="on" {{ $settings['maintenance_mode'] === 'on' ? 'selected' : '' }}>On (Maintenance banner enabled)</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- SECTION 2: DYNAMIC BATTLE COMMISSION CONFIGURATION --}}
            <div class="mb-4 p-3 rounded-3" style="background: rgba(22, 33, 62, 0.7); border: 1.5px solid rgba(212, 175, 55, 0.35);">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h6 class="text-warning fw-bold text-uppercase mb-0" style="letter-spacing: 0.5px;">
                        <i class="fa-solid fa-percent me-1"></i> 2. Dynamic Game Commission & Prize Pool Rules
                    </h6>
                    <span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 10px;">
                        APPLIED ON BATTLE SETTLEMENT
                    </span>
                </div>
                <p class="small text-secondary mb-3">
                    Configure dynamic commission deducted from the match pool ($2 \times \text{Entry Fee}$). Winning prize is calculated dynamically and immutably locked when the battle is set.
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Commission Calculation Mode <span class="text-danger">*</span></label>
                        <select name="battle_commission_mode" id="battleCommissionMode" class="form-select bg-dark text-light border-secondary small">
                            <option value="percentage" {{ ($settings['battle_commission_mode'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>
                                Standard Flat Percentage (e.g. 5% of Pool = 1.9x Prize)
                            </option>
                            <option value="slabs" {{ ($settings['battle_commission_mode'] ?? '') === 'slabs' ? 'selected' : '' }}>
                                Slab / Tier-Based Rules (Custom Range Rules)
                            </option>
                        </select>
                        <small class="text-secondary">Choose between a flat commission rate or customized fee tier slabs.</small>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Default Platform Commission (% of Total Pool)</label>
                        <div class="input-group">
                            <input type="number" step="0.1" name="battle_commission_percentage" id="inputCommissionPct" class="form-control bg-dark text-light border-secondary small font-monospace fw-bold" value="{{ $settings['battle_commission_percentage'] ?? '5' }}" min="0" max="50">
                            <span class="input-group-text bg-dark text-warning border-secondary small">%</span>
                        </div>
                        <small class="text-secondary">Default 5% commission gives 1.9x prize (e.g. 100 Fee &rarr; 200 Pool &rarr; 10 Commission &rarr; 190 Prize).</small>
                    </div>
                </div>

                {{-- Slabs JSON Configuration --}}
                <div class="mb-3 {{ ($settings['battle_commission_mode'] ?? '') === 'slabs' ? '' : 'd-none' }}" id="slabsContainer">
                    <label class="form-label text-white small fw-bold">Commission Slabs JSON Configuration</label>
                    <textarea name="battle_commission_slabs" id="battleCommissionSlabs" rows="6" class="form-control bg-dark text-warning border-secondary font-monospace small" style="tab-size: 2;">{{ $settings['battle_commission_slabs'] ?? '' }}</textarea>
                    <small class="text-secondary">
                        Format: JSON array of objects with <code>min</code>, <code>max</code>, <code>type</code> ("percent" or "fixed"), and <code>value</code>.
                    </small>
                </div>

                {{-- Interactive Commission Calculator --}}
                <div class="p-3 rounded-2" style="background: rgba(10, 15, 30, 0.85); border: 1px dashed rgba(212, 175, 55, 0.4);">
                    <div class="small text-secondary fw-bold text-uppercase mb-2">
                        <i class="fa-solid fa-calculator text-warning me-1"></i> Live Commission & Prize Calculator Preview
                    </div>
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-sm-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-dark text-secondary border-secondary">Test Fee:</span>
                                <input type="number" id="testEntryFee" class="form-control bg-dark text-light border-secondary font-monospace" value="100" min="10" step="10">
                            </div>
                        </div>
                        <div class="col-12 col-sm-8 text-sm-end small d-flex flex-wrap gap-2 align-items-center justify-content-sm-end">
                            <span class="text-light">Total Pool: <strong id="previewPool" class="text-white">🪙 200</strong></span> &bull;
                            <span class="text-danger">Commission: <strong id="previewCommission">🪙 10</strong></span> &bull;
                            <span class="text-success">Winner Prize: <strong id="previewPrize" class="fs-6">🪙 190</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 3: DIRECT UPI / QR CODE PAYMENT GATEWAY SETTINGS --}}
            <div class="mb-4 p-3 rounded-3" style="background: rgba(13, 27, 42, 0.85); border: 1.5px solid rgba(0, 210, 255, 0.4);">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-info fw-bold text-uppercase mb-0" style="letter-spacing: 0.5px;">
                        <i class="fa-solid fa-qrcode me-1"></i> 3. Direct UPI / QR Code Payment Gateway Configuration
                    </h6>
                    <span class="badge {{ ($settings['upi_deposits_enabled'] ?? 'on') === 'on' ? 'bg-success' : 'bg-secondary' }} text-uppercase px-2 py-1" style="font-size: 10px;">
                        {{ ($settings['upi_deposits_enabled'] ?? 'on') === 'on' ? 'Active' : 'Disabled' }}
                    </span>
                </div>
                <p class="small text-secondary mb-3">
                    Configure your official UPI ID, Payee details, QR Code image, and deposit limits. No third-party payment gateway or processing fee required.
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">QR Payment Status Toggle <span class="text-danger">*</span></label>
                        <select name="upi_deposits_enabled" class="form-select bg-dark text-light border-secondary small">
                            <option value="on" {{ ($settings['upi_deposits_enabled'] ?? 'on') === 'on' ? 'selected' : '' }}>On (Allow Players to Add Coins via QR)</option>
                            <option value="off" {{ ($settings['upi_deposits_enabled'] ?? 'on') === 'off' ? 'selected' : '' }}>Off (Disable QR Code Deposits)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">Payee / Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="upi_payee_name" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['upi_payee_name'] }}" required maxlength="100">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label text-white small fw-bold">UPI ID / VPA <span class="text-danger">*</span></label>
                        <input type="text" name="upi_id" class="form-control bg-dark text-warning border-secondary font-monospace small" value="{{ $settings['upi_id'] }}" required maxlength="100">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Minimum Deposit Limit (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="upi_min_deposit" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['upi_min_deposit'] ?? 50 }}" min="10" max="10000" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Maximum Deposit Limit (₹) <span class="text-danger">*</span></label>
                        <input type="number" name="upi_max_deposit" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['upi_max_deposit'] ?? 50000 }}" min="100" max="500000" required>
                    </div>
                </div>

                {{-- QR Code Image Upload and Preview --}}
                <div class="row g-3 mb-3 align-items-center">
                    <div class="col-12 col-md-7">
                        <label class="form-label text-white small fw-bold">Upload Custom QR Code Image</label>
                        <input type="file" name="upi_qr_image" class="form-control bg-dark text-light border-secondary small" accept=".png,.jpg,.jpeg,.webp">
                        <small class="text-secondary d-block mt-1">
                            Recommended size: 500x500px, PNG or JPG (Max 5MB). If left empty, a dynamic UPI QR is auto-generated.
                        </small>
                    </div>
                    <div class="col-12 col-md-5 text-center">
                        <div class="p-2 bg-white rounded-3 d-inline-block shadow" style="max-width: 140px;">
                            @if(!empty($settings['upi_qr_image']))
                                <img src="{{ \App\Services\FileUploadService::url($settings['upi_qr_image']) }}" alt="Active QR Code" class="img-fluid rounded" style="max-height: 120px;">
                            @else
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode('upi://pay?pa=' . $settings['upi_id'] . '&pn=' . $settings['upi_payee_name']) }}" alt="Dynamic QR" class="img-fluid rounded" style="max-height: 120px;">
                            @endif
                        </div>
                        <div class="small text-secondary mt-1" style="font-size: 11px;">
                            {{ !empty($settings['upi_qr_image']) ? 'Custom Uploaded QR' : 'Auto-generated UPI QR' }}
                        </div>
                    </div>
                </div>

                {{-- Instructions in English and Hindi --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Player Deposit Instructions (English)</label>
                        <textarea name="upi_instructions_en" rows="3" class="form-control bg-dark text-light border-secondary small">{{ $settings['upi_instructions_en'] ?? '' }}</textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-white small fw-bold">Player Deposit Instructions (Hindi)</label>
                        <textarea name="upi_instructions_hi" rows="3" class="form-control bg-dark text-light border-secondary small">{{ $settings['upi_instructions_hi'] ?? '' }}</textarea>
                    </div>
                </div>

                {{-- Free Virtual Points Reload (Sandbox Mode) --}}
                <div class="p-3 rounded-3 mt-2" style="background: rgba(14, 20, 44, 0.6); border: 1px dashed rgba(245, 158, 11, 0.3);">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label text-warning small fw-bold mb-0">
                            <i class="fa-solid fa-flask me-1"></i> Free Virtual Points Reload (Sandbox / Demo Testing Mode)
                        </label>
                        <span class="badge {{ ($settings['virtual_points_reload_enabled'] ?? 'off') === 'on' ? 'bg-warning text-dark' : 'bg-success' }} px-2 py-0" style="font-size: 10px;">
                            {{ ($settings['virtual_points_reload_enabled'] ?? 'off') === 'on' ? 'DEMO ACTIVE' : 'LOCKED (STRICT PAYMENT)' }}
                        </span>
                    </div>
                    <select name="virtual_points_reload_enabled" class="form-select bg-dark text-light border-secondary small mb-1">
                        <option value="off" {{ ($settings['virtual_points_reload_enabled'] ?? 'off') === 'off' ? 'selected' : '' }}>Off — Strict Production Mode (Players MUST pay via UPI QR to get coins)</option>
                        <option value="on" {{ ($settings['virtual_points_reload_enabled'] ?? 'off') === 'on' ? 'selected' : '' }}>On — Sandbox / Testing Mode (Show "+ Add Virtual Points" button on wallet)</option>
                    </select>
                    <div class="text-secondary small" style="font-size: 11px;">
                        <i class="fa-solid fa-shield-halved text-info me-1"></i> When OFF (Recommended for production), the "+ Add Virtual Points" free reload card is completely hidden from players, preventing anyone from getting coins without transferring real money.
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center pt-2">
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-save me-1"></i> Save Platform Configurations
                </button>
                <a href="{{ route('admin.ludo.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3 py-1 small">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    {{-- Script for Live Commission Calculator & Mode Toggle --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modeSelect = document.getElementById('battleCommissionMode');
            const slabsContainer = document.getElementById('slabsContainer');
            const pctInput = document.getElementById('inputCommissionPct');
            const slabsTextarea = document.getElementById('battleCommissionSlabs');
            const testFeeInput = document.getElementById('testEntryFee');
            const previewPool = document.getElementById('previewPool');
            const previewCommission = document.getElementById('previewCommission');
            const previewPrize = document.getElementById('previewPrize');

            function updateCalculator() {
                const fee = parseInt(testFeeInput.value) || 0;
                const pool = fee * 2;
                let commission = 0;

                if (modeSelect.value === 'slabs') {
                    try {
                        const slabs = JSON.parse(slabsTextarea.value);
                        let matched = false;
                        for (let s of slabs) {
                            const min = s.min || 0;
                            const max = s.max || Infinity;
                            if (fee >= min && fee <= max) {
                                if (s.type === 'fixed') {
                                    commission = Math.round(s.value);
                                } else if (s.type === 'percent_of_battle' || s.basis === 'battle' || s.basis === 'entry') {
                                    commission = Math.round(fee * (s.value / 100));
                                } else {
                                    commission = Math.round(pool * (s.value / 100));
                                }
                                matched = true;
                                break;
                            }
                        }
                        if (!matched) {
                            commission = Math.round(pool * ((parseFloat(pctInput.value) || 5) / 100));
                        }
                    } catch(e) {
                        commission = Math.round(pool * ((parseFloat(pctInput.value) || 5) / 100));
                    }
                } else {
                    const pct = parseFloat(pctInput.value) || 5;
                    commission = Math.round(pool * (pct / 100));
                }

                commission = Math.max(0, Math.min(commission, pool));
                const prize = pool - commission;

                previewPool.textContent = `🪙 ${pool}`;
                previewCommission.textContent = `🪙 ${commission}`;
                previewPrize.textContent = `🪙 ${prize}`;
            }

            modeSelect?.addEventListener('change', function() {
                if (this.value === 'slabs') {
                    slabsContainer.classList.remove('d-none');
                } else {
                    slabsContainer.classList.add('d-none');
                }
                updateCalculator();
            });

            pctInput?.addEventListener('input', updateCalculator);
            slabsTextarea?.addEventListener('input', updateCalculator);
            testFeeInput?.addEventListener('input', updateCalculator);

            updateCalculator();
        });
    </script>
@endsection
