@extends('layouts.admin')

@section('title', 'System Settings — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Platform System Configurations')

@section('content')
    <div class="admin-card mb-4" style="max-width: 860px;">
        <h5 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-sliders text-warning me-2"></i> Core Platform & Virtual-Points Parameters
        </h5>

        <form action="{{ route('admin.ludo.settings.update') }}" method="POST">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">Platform Brand Title <span class="text-danger">*</span></label>
                    <input type="text" name="platform_name" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['platform_name'] }}" required maxlength="100">
                    <small class="text-secondary">Official name displayed in player browser titlebars and headers.</small>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">Welcome Bonus (New Player Virtual Points)</label>
                    <input type="number" name="welcome_bonus_points" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['welcome_bonus_points'] ?? 100 }}" min="0" max="10000">
                    <small class="text-secondary">Non-cash virtual points credited to newly registered accounts.</small>
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
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">Support Desk Contact Email <span class="text-danger">*</span></label>
                    <input type="email" name="support_email" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['support_email'] }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">Support WhatsApp Number</label>
                    <input type="text" name="support_whatsapp" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['support_whatsapp'] ?? '' }}" placeholder="+91 9876543210">
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">Support Telegram Channel / Desk</label>
                    <input type="text" name="support_telegram" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['support_telegram'] ?? '' }}" placeholder="https://t.me/fsaludoclub">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-white small fw-bold">Support Active Hours</label>
                    <input type="text" name="support_hours" class="form-control bg-dark text-light border-secondary small" value="{{ $settings['support_hours'] ?? '' }}" placeholder="Mon-Sun 10:00 AM - 10:00 PM IST">
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

            <div class="mb-4">
                <label class="form-label text-white small fw-bold">Platform Maintenance Mode</label>
                <select name="maintenance_mode" class="form-select bg-dark text-light border-secondary small">
                    <option value="off" {{ $settings['maintenance_mode'] === 'off' ? 'selected' : '' }}>Off (Normal live operations)</option>
                    <option value="on" {{ $settings['maintenance_mode'] === 'on' ? 'selected' : '' }}>On (Maintenance banner enabled)</option>
                </select>
                <small class="text-secondary">When enabled, players will see a scheduled maintenance notification on the lobby.</small>
            </div>

            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-save me-1"></i> Save Platform Configurations
            </button>
        </form>
    </div>
@endsection
