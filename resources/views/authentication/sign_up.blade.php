<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account — {{ config('ludo.brand_name', 'Fsaludoclub') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/fsaludoclub-icon.png') }}">

    {{-- Local Bootstrap 5 --}}
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    {{-- FontAwesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Google Fonts: Poppins & Cinzel --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Ludo Core Theme --}}
    <link rel="stylesheet" href="{{ asset('css/ludo-theme.css') }}">

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
            max-width: 460px;
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .fsaludo-auth-logo-box {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }

        .fsaludo-auth-logo {
            max-width: 230px;
            width: 80%;
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
            background: rgba(11, 19, 39, 0.92);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1.5px solid rgba(212, 175, 55, 0.45);
            border-radius: 22px;
            padding: 34px 24px 28px;
            position: relative;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8), 0 0 30px rgba(212, 175, 55, 0.12);
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
            padding: 5px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.6), 0 0 10px rgba(212, 175, 55, 0.3);
            white-space: nowrap;
        }

        .bonus-badge {
            background: rgba(212, 175, 55, 0.15);
            border: 1px dashed rgba(255, 215, 0, 0.6);
            color: #ffd700;
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .fsaludo-auth-card .form-control {
            background-color: #ffffff;
            border: 1.5px solid #d4af37;
            color: #0f172a;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 0.92rem;
            font-weight: 500;
        }

        .fsaludo-auth-card .form-control:focus {
            background-color: #ffffff;
            border-color: #00d2ff;
            color: #0f172a;
            box-shadow: 0 0 0 0.25rem rgba(0, 210, 255, 0.25);
        }

        .fsaludo-auth-card .input-group-text {
            background: #0f172a;
            border: 1.5px solid #d4af37;
            color: #ffd700;
            border-radius: 12px 0 0 12px;
            padding: 0 14px;
        }

        .fsaludo-auth-card .input-group .form-control {
            border-radius: 0 12px 12px 0;
            border-left: none;
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
        }

        .btn-gold-action:hover {
            background: linear-gradient(135deg, #fff2a3 0%, #e6be44 50%, #ad7c00 100%);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.55);
            transform: translateY(-2px);
        }

        .fsaludo-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 18px 0;
            color: #64748b;
            font-size: 0.78rem;
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
            margin-top: 16px;
            line-height: 1.45;
        }

        .fsaludo-legal-notice a {
            color: #ffd700;
            text-decoration: underline;
            text-underline-offset: 2px;
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
                <i class="fa-solid fa-crown text-warning"></i> Player Registration
            </div>

            {{-- Welcome Bonus Banner --}}
            <div class="bonus-badge">
                <i class="fa-solid fa-gift fs-6 text-warning"></i>
                <span>Get <strong>100 Free Welcome Coins</strong> on registration!</span>
            </div>

            {{-- Flash Messages --}}
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

            {{-- Registration Form --}}
            <form action="{{ url('/register') }}" method="POST" id="registerForm">
                @csrf

                {{-- Full Name --}}
                <div class="mb-3">
                    <label for="nameInput" class="form-label text-warning-emphasis small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                        Player Name
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" class="form-control" id="nameInput"
                               placeholder="e.g. Rahul Sharma" name="name"
                               value="{{ old('name') }}" required autofocus>
                    </div>
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label for="emailInput" class="form-label text-warning-emphasis small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                        Email Address
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" class="form-control" id="emailInput"
                               placeholder="e.g. rahul@example.com" name="email"
                               value="{{ old('email') }}" required>
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label for="passwordInput" class="form-label text-warning-emphasis small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                        Password (Min. 8 characters)
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="form-control" id="passwordInput"
                               placeholder="Create secure password" name="password" required minlength="8">
                        <button class="btn btn-outline-warning border-start-0 bg-dark text-warning px-3" type="button" id="togglePasswordBtn" aria-label="Toggle password visibility">
                            <i class="fa-solid fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                {{-- Referral Code (Optional) --}}
                <div class="mb-3">
                    <label for="referralInput" class="form-label text-warning-emphasis small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">
                        Referral Code <span class="text-secondary fw-normal">(Optional)</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-ticket text-warning"></i></span>
                        <input type="text" class="form-control text-uppercase" id="referralInput"
                               placeholder="Enter 6-digit invite code" name="referral_code"
                               value="{{ request('ref', old('referral_code')) }}">
                    </div>
                </div>

                {{-- CREATE ACCOUNT Button --}}
                <button type="submit" class="btn btn-gold-action w-100 mb-2 mt-2" id="submitRegisterBtn">
                    CREATE ACCOUNT
                </button>
            </form>

            <div class="fsaludo-divider">OR</div>

            {{-- Returning User --}}
            <a href="{{ url('/showlogin') }}" class="btn btn-outline-warning w-100 py-2 rounded-3 text-uppercase fw-semibold small" style="border-color: rgba(212, 175, 55, 0.5); color: #ffd700;">
                <i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Already Have An Account? Sign In
            </a>

            {{-- Legal Disclaimer --}}
            <div class="fsaludo-legal-notice">
                By registering, you agree to our <a href="{{ url('/rules') }}">Terms of Service</a> & <a href="{{ url('/rules') }}">Fair Play Policy</a>.
            </div>
        </div>
    </div>

    {{-- Interactive Scripts --}}
    <script>
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

        // Double submit protection
        document.getElementById('registerForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('submitRegisterBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> CREATING ACCOUNT...';
            }
        });
    </script>
</body>
</html>
