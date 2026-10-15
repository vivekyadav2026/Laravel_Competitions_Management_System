<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login — {{ config('ludo.brand_name', 'Fsaludoclub') }} Console</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/fsaludoclub-icon.png') }}">

    {{-- Local Bootstrap 5 --}}
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    {{-- FontAwesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Google Fonts: Poppins & Cinzel --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body.admin-auth-body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: #040711 url('{{ asset("assets/images/fsaludoclub-auth-bg.jpg") }}') no-repeat center center / cover fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            color: #f8fafc;
            position: relative;
        }

        .admin-auth-overlay {
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 50% 30%, rgba(5, 8, 17, 0.78) 0%, rgba(3, 5, 12, 0.96) 85%);
            z-index: 1;
        }

        .admin-auth-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding: 24px 16px;
        }

        .admin-auth-logo {
            max-width: 210px;
            height: auto;
            filter: drop-shadow(0 10px 24px rgba(212, 175, 55, 0.45));
        }

        .admin-auth-card {
            background: rgba(11, 18, 36, 0.95);
            backdrop-filter: blur(16px);
            border: 1.5px solid rgba(212, 175, 55, 0.5);
            border-radius: 20px;
            padding: 36px 26px 28px;
            position: relative;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85), 0 0 30px rgba(212, 175, 55, 0.14);
        }

        .admin-badge-pill {
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #162240 0%, #0a1022 100%);
            border: 1.5px solid #ffd700;
            color: #ffd700;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 5px 18px;
            border-radius: 50px;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.7);
        }

        .admin-auth-card .form-control {
            background-color: #070c1a;
            border: 1.5px solid rgba(212, 175, 55, 0.35);
            color: #ffffff;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 0.92rem;
        }

        .admin-auth-card .form-control:focus {
            background-color: #091024;
            border-color: #ffd700;
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.2);
        }

        .admin-auth-card .input-group-text {
            background-color: #050812;
            border: 1.5px solid rgba(212, 175, 55, 0.35);
            color: #ffd700;
            border-radius: 10px 0 0 10px;
        }

        .btn-admin-gold {
            background: linear-gradient(135deg, #ffe279 0%, #d4af37 50%, #996e00 100%);
            color: #040711 !important;
            border: 1px solid #ffd700;
            font-weight: 800;
            letter-spacing: 1.2px;
            border-radius: 10px;
            padding: 12px;
            text-transform: uppercase;
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.35);
            transition: all 0.2s ease;
        }

        .btn-admin-gold:hover {
            background: linear-gradient(135deg, #fff2a3 0%, #e6be44 50%, #ad7c00 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.5);
        }
    </style>
</head>
<body class="admin-auth-body">
    <div class="admin-auth-overlay"></div>

    <div class="admin-auth-container">
        {{-- Brand Logo --}}
        <div class="text-center mb-4">
            <a href="{{ url('/') }}">
                <img src="{{ asset('assets/images/fsaludoclub-logo.png') }}" alt="{{ config('ludo.brand_name', 'Fsaludoclub') }}" class="admin-auth-logo">
            </a>
        </div>

        {{-- Admin Login Card --}}
        <div class="admin-auth-card">
            <div class="admin-badge-pill">
                <i class="fa-solid fa-crown me-1"></i> Admin Console Login
            </div>

            <div class="text-center mb-3">
                <h5 class="fw-bold text-white mb-1" style="font-family: 'Cinzel', serif;">{{ strtoupper(config('ludo.brand_name', 'FSALUDOCLUB')) }}</h5>
                <p class="text-secondary small mb-0">Authorized Administrative & Operations Portal</p>
            </div>

            {{-- Alerts --}}
            @if(session('success'))
                <div class="alert alert-success py-2 px-3 small rounded-3 mb-3">
                    <i class="fa-solid fa-check-circle me-1"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.login.post') }}" method="POST" id="adminLoginForm">
                @csrf

                <div class="mb-3">
                    <label for="adminEmail" class="form-label text-warning small fw-semibold text-uppercase">Admin Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user-shield"></i></span>
                        <input type="email" name="email" id="adminEmail" class="form-control" value="{{ old('email') }}" placeholder="Enter admin email" autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="adminPassword" class="form-label text-warning small fw-semibold text-uppercase">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-key"></i></span>
                        <input type="password" name="password" id="adminPassword" class="form-control" value="" placeholder="Enter admin password" autocomplete="current-password" required>
                        <button class="btn btn-outline-secondary border-secondary text-warning" type="button" id="toggleAdminPwd">
                            <i class="fa-solid fa-eye" id="toggleAdminPwdIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" name="remember_me" id="rememberAdmin" checked>
                        <label class="form-check-label text-secondary small" for="rememberAdmin">Keep session active</label>
                    </div>
                    <span class="badge bg-dark border border-warning border-opacity-50 text-warning" style="font-size: 10px;">RBAC Protected</span>
                </div>

                <button type="submit" class="btn btn-admin-gold w-100" id="adminSubmitBtn">
                    <i class="fa-solid fa-unlock-keyhole me-1"></i> Sign In to Admin Console
                </button>
            </form>

            <div class="mt-3 pt-3 border-top border-secondary border-opacity-25 text-center">
                <a href="{{ url('/') }}" class="small text-info text-decoration-none">
                    <i class="fa-solid fa-arrow-left me-1"></i> Return to Player Lobby
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('toggleAdminPwd')?.addEventListener('click', function() {
            const pwd = document.getElementById('adminPassword');
            const icon = document.getElementById('toggleAdminPwdIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        document.getElementById('adminLoginForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('adminSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> AUTHENTICATING...';
            }
        });
    </script>
</body>
</html>
