<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In & Play — {{ config('ludo.brand_name', 'Fsaludoclub') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/fsaludoclub-icon.png') }}">

    {{-- Local Bootstrap 5 --}}
    <link rel="stylesheet" href="{{ versioned_asset('css/bootstrap.min.css') }}">
    
    {{-- FontAwesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Google Fonts: Poppins & Cinzel --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Ludo Core Theme --}}
    <link rel="stylesheet" href="{{ versioned_asset('css/ludo-theme.css') }}">

    <style>
        body.fsaludo-auth-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: #040711 url('{{ asset("assets/images/fsaludoclub-auth-bg.jpg") }}') no-repeat center top / cover fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            color: #ffffff;
            position: relative;
            overflow-x: hidden;
        }

        .fsaludo-auth-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 50% 30%, rgba(4, 7, 17, 0.6) 0%, rgba(3, 6, 14, 0.94) 80%);
            z-index: 1;
        }

        .fsaludo-auth-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .fsaludo-auth-logo-box {
            text-align: center;
            margin-bottom: 22px;
            position: relative;
        }

        .fsaludo-auth-logo {
            max-width: 250px;
            width: 85%;
            height: auto;
            filter: drop-shadow(0 12px 28px rgba(212, 175, 55, 0.45));
            animation: authLogoFloat 4s ease-in-out infinite;
        }

        @keyframes authLogoFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .fsaludo-auth-card {
            width: 100%;
            background: rgba(11, 19, 39, 0.94);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1.5px solid rgba(212, 175, 55, 0.55);
            border-radius: 22px;
            padding: 34px 24px 28px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.85), 0 0 35px rgba(212, 175, 55, 0.15);
        }

        .fsaludo-card-badge {
            position: absolute;
            top: -16px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #16203a 0%, #0d1424 100%);
            border: 1.5px solid #d4af37;
            color: #ffd700;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 5px 22px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6), 0 0 10px rgba(212, 175, 55, 0.35);
            white-space: nowrap;
        }

        .bonus-banner {
            background: rgba(212, 175, 55, 0.12);
            border: 1px dashed rgba(255, 215, 0, 0.5);
            color: #ffd700;
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .input-group-gold {
            background: rgba(15, 23, 42, 0.8);
            border: 1.5px solid #d4af37;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .input-group-gold:focus-within {
            border-color: #ffd700;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
        }

        .input-group-gold .input-group-text {
            background: #0f172a;
            border: none;
            color: #ffd700;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 12px 14px;
        }

        .input-group-gold .form-control {
            background: #ffffff;
            border: none;
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 12px 14px;
        }

        .input-group-gold .form-control:focus {
            background: #ffffff;
            color: #0f172a;
            box-shadow: none;
        }

        .btn-gold-action {
            background: linear-gradient(135deg, #ffe279 0%, #d4af37 50%, #996e00 100%);
            color: #040711 !important;
            border: 1px solid #ffd700;
            font-weight: 800;
            letter-spacing: 1.5px;
            border-radius: 12px;
            padding: 13px;
            text-transform: uppercase;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.35);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-gold-action:hover {
            background: linear-gradient(135deg, #fff2a3 0%, #e6be44 50%, #ad7c00 100%);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.55);
            transform: translateY(-2px);
        }

        .btn-gold-action:active {
            transform: translateY(0);
        }

        .btn-gold-action:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .fsaludo-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 18px 0;
            color: #64748b;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .fsaludo-divider::before,
        .fsaludo-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(212, 175, 55, 0.25);
        }

        .fsaludo-divider::before { margin-right: .75em; }
        .fsaludo-divider::after { margin-left: .75em; }

        .fsaludo-legal-notice {
            font-size: 0.75rem;
            color: #94a3b8;
            text-align: center;
            margin-top: 18px;
            line-height: 1.45;
        }

        .fsaludo-legal-notice a {
            color: #ffd700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        /* ==========================================================================
           OTP POPUP MODAL (Matching User Uploaded UI Reference)
           ========================================================================== */
        .otp-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(3, 6, 14, 0.82);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            transition: opacity 0.28s ease;
        }

        .otp-modal-backdrop.show {
            display: flex;
            opacity: 1;
        }

        .otp-modal-card {
            position: relative;
            width: 100%;
            max-width: 430px;
            background: #0b111d;
            border: 1.5px solid #d4af37;
            border-radius: 24px;
            padding: 34px 24px 28px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.9), 0 0 40px rgba(212, 175, 55, 0.22);
            transform: scale(0.92);
            transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-align: center;
        }

        .otp-modal-backdrop.show .otp-modal-card {
            transform: scale(1);
        }

        .otp-pill-header {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: 1.8px solid #d4af37;
            border-radius: 50px;
            padding: 6px 28px;
            color: #ffd700;
            font-family: 'Cinzel', serif;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            background: #0b111d;
            margin: 0 auto 18px;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.2);
        }

        .otp-pill-header .sparkle {
            color: #ffe066;
            font-size: 0.85rem;
        }

        .otp-close-btn {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 1.5px solid #d4af37;
            background: rgba(15, 23, 42, 0.6);
            color: #ffd700;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
        }

        .otp-close-btn:hover {
            background: rgba(212, 175, 55, 0.25);
            color: #ffffff;
            transform: rotate(90deg) scale(1.05);
        }

        .otp-subtitle {
            color: #cbd5e1;
            font-size: 0.88rem;
            line-height: 1.55;
            margin-bottom: 22px;
            padding: 0 6px;
        }

        .otp-subtitle .phone-highlight {
            color: #ffd700;
            font-weight: 700;
            white-space: nowrap;
        }

        .otp-subtitle .checkmark {
            color: #10b981;
            font-weight: 800;
            margin-left: 3px;
        }

        .otp-inputs-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 18px;
        }

        .otp-digit-input {
            width: 48px;
            height: 58px;
            border: 1.5px solid #d4af37;
            border-radius: 12px;
            background: rgba(12, 18, 32, 0.8);
            color: #ffd700;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            outline: none;
            transition: all 0.2s ease;
            caret-color: #ffd700;
            font-family: 'Poppins', monospace;
            padding: 0;
        }

        @media (max-width: 420px) {
            .otp-digit-input {
                width: 42px;
                height: 52px;
                font-size: 1.35rem;
                border-radius: 10px;
            }
            .otp-inputs-row {
                gap: 6px;
            }
        }

        .otp-digit-input:focus {
            border-color: #ffe066;
            background: rgba(22, 33, 62, 0.95);
            box-shadow: 0 0 16px rgba(255, 215, 0, 0.5);
            transform: translateY(-2px);
        }

        .otp-digit-input.is-filled {
            border-color: #ffd700;
            background: rgba(26, 38, 70, 0.9);
        }

        .otp-timer-box {
            font-size: 0.9rem;
            color: #e2e8f0;
            margin-bottom: 22px;
            font-weight: 500;
        }

        .otp-timer-highlight {
            color: #ffd700;
            font-weight: 800;
        }

        .btn-verify-login {
            width: 100%;
            background: linear-gradient(180deg, #ffdb58 0%, #d4af37 50%, #a67c00 100%);
            color: #040711 !important;
            border: 1.5px solid #ffe279;
            border-radius: 14px;
            padding: 13px 20px;
            font-weight: 900;
            font-size: 0.98rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4), inset 0 2px 4px rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-verify-login::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 10%;
            right: 10%;
            height: 6px;
            background: rgba(255, 255, 255, 0.35);
            border-radius: 10px;
            pointer-events: none;
        }

        .btn-verify-login:hover {
            background: linear-gradient(180deg, #ffe57f 0%, #e6be44 50%, #b8860b 100%);
            box-shadow: 0 10px 30px rgba(212, 175, 55, 0.6);
            transform: translateY(-2px);
        }

        .btn-verify-login:active {
            transform: translateY(0);
        }

        .btn-verify-login:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none !important;
        }

        .otp-alert {
            display: none;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 0.82rem;
            margin-bottom: 16px;
            text-align: left;
        }

        .otp-alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #f87171;
        }

        .otp-alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #34d399;
        }
    </style>
</head>
<body class="fsaludo-auth-body">

    <div class="fsaludo-auth-overlay"></div>

    <div class="fsaludo-auth-container">
        {{-- Emblem Logo --}}
        <div class="fsaludo-auth-logo-box">
            <a href="{{ url('/') }}">
                <img src="{{ asset('assets/images/fsaludoclub-logo.png') }}" alt="{{ config('ludo.brand_name', 'Fsaludoclub') }} Logo" class="fsaludo-auth-logo">
            </a>
        </div>

        {{-- Authentication Card with Gold Rim --}}
        <div class="fsaludo-auth-card">
            {{-- Floating Badge --}}
            <div class="fsaludo-card-badge">
                <i class="fa-solid fa-crown text-warning"></i> Quick Player Login
            </div>

            {{-- Welcome Bonus Notice --}}
            <div class="bonus-banner">
                <i class="fa-solid fa-gift text-warning"></i>
                <span>Get <strong>100 Free Welcome Coins</strong> on login / register!</span>
            </div>

            {{-- Global Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success py-2 px-3 small rounded-3 mb-3 border-0 bg-success bg-opacity-25 text-success-emphasis">
                    <i class="fa-solid fa-check-circle me-1"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('message'))
                <div class="alert alert-info py-2 px-3 small rounded-3 mb-3 border-0 bg-info bg-opacity-25 text-info-emphasis">
                    <i class="fa-solid fa-info-circle me-1"></i> {{ session('message') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 border-0 bg-danger bg-opacity-25 text-danger-emphasis">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 border-0 bg-danger bg-opacity-25 text-danger-emphasis">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Alert area for OTP dispatch errors --}}
            <div id="mainAuthAlert" class="alert alert-danger py-2 px-3 small rounded-3 mb-3 d-none border-0 bg-danger bg-opacity-25 text-danger-emphasis"></div>

            {{-- PRIMARY: Mobile OTP Login Section --}}
            <div id="otpLoginSection">
                <form id="sendOtpForm">
                    @csrf

                    {{-- Mobile Number Input --}}
                    <div class="mb-3">
                        <label for="mobileInput" class="form-label text-warning-emphasis small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                            Mobile Number
                        </label>
                        <div class="input-group input-group-gold">
                            <span class="input-group-text"><i class="fa-solid fa-mobile-screen me-1"></i> +91</span>
                            <input type="tel" class="form-control" id="mobileInput"
                                   placeholder="Enter 10-digit mobile number" name="phone"
                                   maxlength="10" inputmode="numeric" pattern="[0-9]*"
                                   value="{{ old('phone', request('phone')) }}" required autofocus>
                        </div>
                        <div class="text-secondary small mt-1" style="font-size: 0.78rem;">
                            <i class="fa-solid fa-shield-halved text-warning me-1"></i> You will receive an OTP via SMS for instant verification.
                        </div>
                    </div>

                    {{-- Optional Referral Code Toggle --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="javascript:void(0)" class="small text-warning text-decoration-none" id="toggleReferralLink">
                                <i class="fa-solid fa-tag me-1"></i> Have a Referral Code?
                            </a>
                        </div>
                        <div id="referralBox" class="{{ request('ref') ? '' : 'd-none' }} mt-2">
                            <div class="input-group input-group-gold">
                                <span class="input-group-text"><i class="fa-solid fa-ticket"></i></span>
                                <input type="text" class="form-control" id="referralCodeInput"
                                       placeholder="Enter Referral Code" name="referral_code"
                                       value="{{ old('referral_code', request('ref')) }}"
                                       maxlength="30" style="text-transform: uppercase;">
                            </div>
                        </div>
                    </div>

                    {{-- GET OTP / CONTINUE Button --}}
                    <button type="submit" class="btn btn-gold-action w-100 mb-2" id="sendOtpBtn">
                        <i class="fa-solid fa-paper-plane me-2"></i> GET OTP & CONTINUE
                    </button>
                </form>
            </div>

            {{-- SECONDARY: Email & Password Sign In (Collapsible for Admins/Staff) --}}
            <div id="passwordLoginSection" class="d-none">
                <form action="{{ route('login.post') }}" method="POST" id="passwordLoginForm">
                    @csrf

                    <div class="mb-3">
                        <label for="emailInput" class="form-label text-warning-emphasis small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                            Email / 10-Digit Mobile Number
                        </label>
                        <div class="input-group input-group-gold">
                            <span class="input-group-text"><i class="fa-solid fa-user-lock"></i></span>
                            <input type="text" class="form-control" id="emailInput"
                                   placeholder="Enter email or 10-digit mobile" name="email"
                                   value="{{ old('email') }}" required autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="passwordInput" class="form-label text-warning-emphasis small fw-semibold text-uppercase mb-0" style="letter-spacing: 0.5px;">
                                Password
                            </label>
                        </div>
                        <div class="input-group input-group-gold">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="passwordInput"
                                   placeholder="Enter your password" name="password" required>
                            <button class="btn btn-outline-warning border-0 bg-dark text-warning px-3" type="button" id="togglePasswordBtn" aria-label="Toggle password visibility">
                                <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe" {{ old('remember_me') ? 'checked' : '' }}>
                            <label class="form-check-label text-secondary small" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        <a href="{{ url('/support') }}" class="small text-warning text-decoration-none">Need help?</a>
                    </div>

                    <button type="submit" class="btn btn-gold-action w-100 mb-2" id="submitPasswordBtn">
                        LOG IN WITH PASSWORD
                    </button>
                </form>
            </div>

            <div class="fsaludo-divider">OPTIONS</div>

            {{-- Mode Switch Button --}}
            <button type="button" class="btn btn-outline-warning w-100 py-2 mb-2 rounded-3 text-uppercase fw-semibold small" id="switchLoginModeBtn" style="border-color: rgba(212, 175, 55, 0.45); color: #ffd700;">
                <i class="fa-solid fa-key me-1"></i> Sign In with Password
            </button>

            {{-- Register Option (Prominent for new users) --}}
            <div class="text-center mt-3 pt-3" style="border-top: 1px solid rgba(212, 175, 55, 0.2);">
                <span class="text-secondary small d-block mb-2">Don't have an account yet?</span>
                <a href="{{ route('register', request()->only('ref')) }}" class="btn btn-outline-light w-100 py-2 rounded-3 text-uppercase fw-bold small text-decoration-none d-flex align-items-center justify-content-center gap-2" id="registerNowBtn" style="border-color: rgba(212, 175, 55, 0.5); color: #ffd700; background: rgba(212, 175, 55, 0.08); letter-spacing: 1px;">
                    <i class="fa-solid fa-user-plus text-warning"></i> Register Now &bull; Create New Account
                </a>
            </div>

            {{-- Legal Disclaimer --}}
            <div class="fsaludo-legal-notice">
                By signing in, you agree to {{ config('ludo.brand_name', 'Fsaludoclub') }} <a href="{{ url('/rules') }}">Terms & Conditions</a> and <a href="{{ url('/rules') }}">Fair Play Policy</a>.
            </div>
        </div>

        {{-- Footer Links --}}
        <div class="d-flex justify-content-center align-items-center gap-3 mt-3 flex-wrap">
            <a href="{{ url('/rules') }}" class="small text-secondary text-decoration-none">
                <i class="fa-solid fa-circle-info me-1 text-warning"></i> Official Rules
            </a>
        </div>
    </div>

    {{-- =========================================================================
         MODAL POPUP: ENTER OTP (Exact UI Structure from Reference Screenshot)
         ========================================================================= --}}
    <div class="otp-modal-backdrop" id="otpModalBackdrop">
        <div class="otp-modal-card">
            {{-- Close Button --}}
            <button type="button" class="otp-close-btn" id="closeOtpModalBtn" aria-label="Close modal">
                <i class="fa-solid fa-xmark"></i>
            </button>

            {{-- Gold Outlined Pill Badge --}}
            <div class="otp-pill-header">
                <span class="sparkle">✦</span>
                <span>ENTER OTP</span>
                <span class="sparkle">✦</span>
            </div>

            {{-- Modal Alert Box --}}
            <div class="otp-alert" id="otpModalAlert"></div>

            {{-- Subtitle with Mobile Number & Checkmark --}}
            <div class="otp-subtitle">
                We have sent the verification code to your mobile number
                <span class="phone-highlight" id="otpPhoneDisplay">+91 0000000000</span>
                <span class="checkmark">✓</span>
            </div>

            {{-- 6 OTP Input Boxes --}}
            <div class="otp-inputs-row" id="otpInputsContainer">
                <input type="text" class="otp-digit-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="0" autocomplete="one-time-code" autofocus>
                <input type="text" class="otp-digit-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="1" autocomplete="one-time-code">
                <input type="text" class="otp-digit-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="2" autocomplete="one-time-code">
                <input type="text" class="otp-digit-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="3" autocomplete="one-time-code">
                <input type="text" class="otp-digit-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="4" autocomplete="one-time-code">
                <input type="text" class="otp-digit-input" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="5" autocomplete="one-time-code">
            </div>

            {{-- Resend Code Countdown Timer --}}
            <div class="otp-timer-box" id="otpTimerBox">
                Resend code in <span class="otp-timer-highlight" id="otpTimerDisplay">60s</span>
            </div>
            <div class="otp-timer-box d-none" id="otpResendBox">
                Didn't receive code? <a href="javascript:void(0)" class="text-warning fw-bold text-decoration-none" id="resendOtpBtn">Resend OTP</a>
            </div>

            {{-- Golden Gradient VERIFY & LOGIN Button --}}
            <button type="button" class="btn-verify-login" id="verifyOtpBtn">
                <span>VERIFY & LOGIN</span>
                <span class="fs-6">✦</span>
            </button>
        </div>
    </div>

    {{-- Interactive Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Elements
            const sendOtpForm = document.getElementById('sendOtpForm');
            const mobileInput = document.getElementById('mobileInput');
            const referralCodeInput = document.getElementById('referralCodeInput');
            const sendOtpBtn = document.getElementById('sendOtpBtn');
            const mainAuthAlert = document.getElementById('mainAuthAlert');

            // Modal elements
            const otpModalBackdrop = document.getElementById('otpModalBackdrop');
            const closeOtpModalBtn = document.getElementById('closeOtpModalBtn');
            const otpPhoneDisplay = document.getElementById('otpPhoneDisplay');
            const otpModalAlert = document.getElementById('otpModalAlert');
            const otpInputs = document.querySelectorAll('.otp-digit-input');
            const otpTimerBox = document.getElementById('otpTimerBox');
            const otpTimerDisplay = document.getElementById('otpTimerDisplay');
            const otpResendBox = document.getElementById('otpResendBox');
            const resendOtpBtn = document.getElementById('resendOtpBtn');
            const verifyOtpBtn = document.getElementById('verifyOtpBtn');

            // Toggle Referral
            const toggleReferralLink = document.getElementById('toggleReferralLink');
            const referralBox = document.getElementById('referralBox');
            toggleReferralLink?.addEventListener('click', function() {
                referralBox.classList.toggle('d-none');
                if (!referralBox.classList.contains('d-none')) {
                    referralCodeInput.focus();
                }
            });

            // Toggle Password Login vs OTP Login
            const switchLoginModeBtn = document.getElementById('switchLoginModeBtn');
            const otpLoginSection = document.getElementById('otpLoginSection');
            const passwordLoginSection = document.getElementById('passwordLoginSection');

            switchLoginModeBtn?.addEventListener('click', function() {
                if (otpLoginSection.classList.contains('d-none')) {
                    otpLoginSection.classList.remove('d-none');
                    passwordLoginSection.classList.add('d-none');
                    switchLoginModeBtn.innerHTML = '<i class="fa-solid fa-key me-1"></i> Sign In with Password';
                    mobileInput.focus();
                } else {
                    otpLoginSection.classList.add('d-none');
                    passwordLoginSection.classList.remove('d-none');
                    switchLoginModeBtn.innerHTML = '<i class="fa-solid fa-mobile-screen me-1"></i> Sign In with Mobile OTP';
                    document.getElementById('emailInput')?.focus();
                }
            });

            // Toggle Password Visibility
            document.getElementById('togglePasswordBtn')?.addEventListener('click', function() {
                const pwdInput = document.getElementById('passwordInput');
                const icon = document.getElementById('togglePasswordIcon');
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    pwdInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });

            // Clean mobile input on typing
            mobileInput?.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').slice(0, 10);
            });

            // Timer management
            let timerInterval = null;
            let currentCooldown = 60;
            let currentMobile = '';

            function startOtpCountdown(seconds = 60) {
                clearInterval(timerInterval);
                currentCooldown = seconds;
                otpTimerBox.classList.remove('d-none');
                otpResendBox.classList.add('d-none');
                otpTimerDisplay.textContent = `${currentCooldown}s`;

                timerInterval = setInterval(() => {
                    currentCooldown--;
                    if (currentCooldown > 0) {
                        otpTimerDisplay.textContent = `${currentCooldown}s`;
                    } else {
                        clearInterval(timerInterval);
                        otpTimerBox.classList.add('d-none');
                        otpResendBox.classList.remove('d-none');
                    }
                }, 1000);
            }

            function showModalAlert(message, type = 'danger') {
                otpModalAlert.className = `otp-alert otp-alert-${type}`;
                otpModalAlert.textContent = message;
                otpModalAlert.style.display = 'block';
            }

            function clearModalAlert() {
                otpModalAlert.style.display = 'none';
                otpModalAlert.textContent = '';
            }

            function openOtpModal(formattedPhone, cooldownSeconds = 60) {
                otpPhoneDisplay.textContent = formattedPhone;
                clearModalAlert();
                otpInputs.forEach(input => {
                    input.value = '';
                    input.classList.remove('is-filled');
                });
                otpModalBackdrop.classList.add('show');
                startOtpCountdown(cooldownSeconds);
                setTimeout(() => {
                    otpInputs[0]?.focus();
                }, 200);
            }

            function closeOtpModal() {
                otpModalBackdrop.classList.remove('show');
                clearInterval(timerInterval);
                clearModalAlert();
            }

            closeOtpModalBtn?.addEventListener('click', closeOtpModal);

            // Close on click outside modal card
            otpModalBackdrop?.addEventListener('click', function(e) {
                if (e.target === otpModalBackdrop) {
                    closeOtpModal();
                }
            });

            // Handle Send OTP Submission
            sendOtpForm?.addEventListener('submit', async function(e) {
                e.preventDefault();
                mainAuthAlert.classList.add('d-none');
                mainAuthAlert.textContent = '';

                const rawPhone = mobileInput.value.trim();
                if (!/^[6-9]\d{9}$/.test(rawPhone)) {
                    mainAuthAlert.textContent = 'Please enter a valid 10-digit Indian mobile number.';
                    mainAuthAlert.classList.remove('d-none');
                    mobileInput.focus();
                    return;
                }

                currentMobile = rawPhone;

                sendOtpBtn.disabled = true;
                const originalBtnHtml = sendOtpBtn.innerHTML;
                sendOtpBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> SENDING OTP...';

                try {
                    const response = await fetch("{{ route('auth.otp.send') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            phone: currentMobile
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        openOtpModal(data.formatted_phone || `+91 ${currentMobile}`, data.cooldown || 60);
                    } else {
                        mainAuthAlert.textContent = data.message || 'Unable to send OTP. Please try again.';
                        mainAuthAlert.classList.remove('d-none');
                    }
                } catch (err) {
                    mainAuthAlert.textContent = 'Network error while contacting server. Please check your connection.';
                    mainAuthAlert.classList.remove('d-none');
                } finally {
                    sendOtpBtn.disabled = false;
                    sendOtpBtn.innerHTML = originalBtnHtml;
                }
            });

            // Handle Resend OTP
            resendOtpBtn?.addEventListener('click', async function(e) {
                e.preventDefault();
                if (!currentMobile) return;

                resendOtpBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Resending...';
                clearModalAlert();

                try {
                    const response = await fetch("{{ route('auth.otp.resend') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            phone: currentMobile
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showModalAlert('A new verification code has been dispatched.', 'success');
                        startOtpCountdown(data.cooldown || 60);
                        otpInputs.forEach(inp => { inp.value = ''; inp.classList.remove('is-filled'); });
                        otpInputs[0]?.focus();
                    } else {
                        showModalAlert(data.message || 'Failed to resend OTP. Please try again in a moment.', 'danger');
                    }
                } catch (err) {
                    showModalAlert('Network error. Please try again.', 'danger');
                } finally {
                    resendOtpBtn.textContent = 'Resend OTP';
                }
            });

            // Handle 6-Digit OTP Box Interactions
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const val = this.value.replace(/\D/g, '');
                    this.value = val ? val[0] : '';

                    if (this.value) {
                        this.classList.add('is-filled');
                        // Advance to next box
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    } else {
                        this.classList.remove('is-filled');
                    }

                    // Check if all 6 boxes are filled
                    checkAndAutoSubmit();
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace') {
                        if (!this.value && index > 0) {
                            otpInputs[index - 1].focus();
                            otpInputs[index - 1].value = '';
                            otpInputs[index - 1].classList.remove('is-filled');
                        } else {
                            this.value = '';
                            this.classList.remove('is-filled');
                        }
                    } else if (e.key === 'ArrowLeft' && index > 0) {
                        otpInputs[index - 1].focus();
                    } else if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    } else if (e.key === 'Enter') {
                        triggerVerify();
                    }
                });

                // Paste support (distributes up to 6 digits)
                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = pastedData.replace(/\D/g, '').slice(0, 6);

                    if (digits.length > 0) {
                        for (let i = 0; i < digits.length; i++) {
                            if (otpInputs[i]) {
                                otpInputs[i].value = digits[i];
                                otpInputs[i].classList.add('is-filled');
                            }
                        }
                        const focusIdx = Math.min(digits.length, otpInputs.length - 1);
                        otpInputs[focusIdx].focus();
                        checkAndAutoSubmit();
                    }
                });
            });

            function getEnteredOtp() {
                let code = '';
                otpInputs.forEach(input => {
                    code += input.value.trim();
                });
                return code;
            }

            function checkAndAutoSubmit() {
                const code = getEnteredOtp();
                if (code.length === 6) {
                    triggerVerify();
                }
            }

            async function triggerVerify() {
                const code = getEnteredOtp();
                if (code.length < 6) {
                    showModalAlert('Please enter the full 6-digit verification code.', 'danger');
                    return;
                }

                clearModalAlert();
                verifyOtpBtn.disabled = true;
                const originalVerifyHtml = verifyOtpBtn.innerHTML;
                verifyOtpBtn.innerHTML = '<span><i class="fa-solid fa-spinner fa-spin me-2"></i> VERIFYING...</span>';

                try {
                    const response = await fetch("{{ route('auth.otp.verify') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            phone: currentMobile,
                            otp: code,
                            referral_code: referralCodeInput ? referralCodeInput.value.trim() : null
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showModalAlert('Verification successful! Logging in...', 'success');
                        verifyOtpBtn.innerHTML = '<span><i class="fa-solid fa-check me-2"></i> SUCCESS!</span>';
                        setTimeout(() => {
                            window.location.href = data.redirect || "{{ url('/') }}";
                        }, 500);
                    } else {
                        showModalAlert(data.message || 'Incorrect verification code. Please try again.', 'danger');
                        otpInputs.forEach(inp => { inp.value = ''; inp.classList.remove('is-filled'); });
                        otpInputs[0]?.focus();
                        verifyOtpBtn.disabled = false;
                        verifyOtpBtn.innerHTML = originalVerifyHtml;
                    }
                } catch (err) {
                    showModalAlert('Server error occurred during verification. Please try again.', 'danger');
                    verifyOtpBtn.disabled = false;
                    verifyOtpBtn.innerHTML = originalVerifyHtml;
                }
            }

            verifyOtpBtn?.addEventListener('click', triggerVerify);
        });
    </script>
</body>
</html>
