@props([
    'coins' => 0,
    'referral' => '0.00'
])

<header class="ludo-header">
    <div class="d-flex align-items-center gap-2">
        {{-- Hamburger Button --}}
        <button id="ludoHamburgerBtn" class="ludo-hamburger-btn" aria-label="Open Navigation Drawer" type="button">
            <span></span>
            <span></span>
            <span></span>
        </button>

        {{-- Game Logo --}}
        <a href="{{ url('/') }}" class="d-inline-flex align-items-center">
            <img src="{{ asset('assets/images/fsaludoclub-logo.png') }}" alt="{{ config('ludo.brand_name', 'Fsaludoclub') }} Logo" class="ludo-header-logo">
        </a>
    </div>

    {{-- Header Balances --}}
    <x-ludo.balance-cards :coins="$coins" :referral="$referral" />
</header>
