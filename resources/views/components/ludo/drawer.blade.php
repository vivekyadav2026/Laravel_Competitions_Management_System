@props([
    'coins' => 0,
    'referral' => '0.00'
])

{{-- Backdrop --}}
<div id="ludoDrawerBackdrop" class="ludo-drawer-backdrop"></div>

{{-- Slideout Drawer (Screenshot #3 Reference) --}}
<aside id="ludoDrawer" class="ludo-drawer">
    {{-- Top Box with Gold Corner Brackets and Logo --}}
    <div class="ludo-drawer-brand-card">
        {{-- Close Button [X] --}}
        <button id="ludoDrawerCloseBtn" class="ludo-drawer-close-btn" aria-label="Close Drawer" type="button">
            <i class="fa-solid fa-xmark"></i>
        </button>

        {{-- Gold Corner Accent Brackets --}}
        <div class="ludo-corner-bracket corner-top-left"></div>
        <div class="ludo-corner-bracket corner-bottom-right"></div>

        {{-- Center Logo --}}
        <div class="text-center py-2">
            <img src="{{ asset('assets/ludo/logo.svg') }}" alt="Royal Ludo Logo" class="ludo-drawer-brand-logo">
        </div>
    </div>

    {{-- Menu Items List (White Pill Cards with Green Double Chevron ») --}}
    <ul class="ludo-drawer-menu-list">
        <li>
            <a href="{{ url('/profile') }}" class="ludo-drawer-pill-item {{ request()->is('profile') ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-info">
                        <i class="fa-solid fa-user-circle fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">My Profile</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/play') }}" class="ludo-drawer-pill-item {{ request()->is('play') || request()->is('/') ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-primary">
                        <i class="fa-solid fa-play fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">Play</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/wallet') }}" class="ludo-drawer-pill-item {{ request()->is('wallet') ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-warning">
                        <i class="fa-solid fa-coins fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">My Wallet</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/refer') }}" class="ludo-drawer-pill-item {{ request()->is('refer') ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-danger">
                        <i class="fa-solid fa-gift fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">Refer and Earn</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/history') }}" class="ludo-drawer-pill-item {{ request()->is('history') ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-secondary">
                        <i class="fa-solid fa-clock-rotate-left fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">History</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/kyc') }}" class="ludo-drawer-pill-item {{ request()->is('kyc') ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-info">
                        <i class="fa-solid fa-id-card fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">KYC Verification</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/support') }}" class="ludo-drawer-pill-item {{ request()->is('support') ? 'active' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-success">
                        <i class="fa-solid fa-headset fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">Support</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="#" class="ludo-drawer-pill-item js-ludo-theme-toggle">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-warning">
                        <i class="fa-solid fa-moon fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">Dark Mode</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>
        <li>
            <a href="#" class="ludo-drawer-pill-item js-install-app">
                <div class="d-flex align-items-center gap-3">
                    <span class="ludo-drawer-pill-icon text-primary">
                        <i class="fa-solid fa-mobile-screen-button fs-5"></i>
                    </span>
                    <span class="ludo-drawer-pill-text">Install App</span>
                </div>
                <span class="ludo-drawer-pill-arrow">&raquo;</span>
            </a>
        </li>

        @auth
            <li class="mt-2">
                <a href="{{ url('/logout') }}" class="ludo-drawer-pill-item border-danger border-opacity-50 text-danger">
                    <div class="d-flex align-items-center gap-3">
                        <span class="ludo-drawer-pill-icon text-danger">
                            <i class="fa-solid fa-right-from-bracket fs-5"></i>
                        </span>
                        <span class="ludo-drawer-pill-text text-danger">Log Out</span>
                    </div>
                    <span class="ludo-drawer-pill-arrow text-danger">&raquo;</span>
                </a>
            </li>
        @else
            <li class="mt-2">
                <a href="{{ url('/showlogin') }}" class="ludo-drawer-pill-item border-info border-opacity-50">
                    <div class="d-flex align-items-center gap-3">
                        <span class="ludo-drawer-pill-icon text-info">
                            <i class="fa-solid fa-arrow-right-to-bracket fs-5"></i>
                        </span>
                        <span class="ludo-drawer-pill-text">Sign In / Register</span>
                    </div>
                    <span class="ludo-drawer-pill-arrow text-info">&raquo;</span>
                </a>
            </li>
        @endauth
    </ul>
</aside>
