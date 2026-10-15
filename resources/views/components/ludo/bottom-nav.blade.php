@props([
    'active' => 'home'
])

@php
    $isWallet = $active === 'wallet' || request()->is('wallet*');
    $isRefer = $active === 'refer' || request()->is('refer*');
    $isHome = $active === 'home' || request()->is('/') || request()->is('play*') || request()->is('battles*');
    $isSupport = $active === 'support' || request()->is('support*');
    $isProfile = $active === 'profile' || request()->is('profile*');
@endphp

<nav class="ludo-bottom-nav" id="ludoBottomNav" aria-label="Bottom Navigation">
    {{-- 1. Wallet --}}
    <a href="{{ url('/wallet') }}" class="ludo-nav-item {{ $isWallet ? 'active' : '' }}" title="My Wallet" data-tab="wallet">
        <div class="ludo-nav-curly-arc" aria-hidden="true">
            <svg viewBox="0 0 84 22" preserveAspectRatio="none">
                <path d="M 0,1 C 18,1 25,20 42,20 C 59,20 66,1 84,1" fill="none" stroke="#00d2ff" stroke-width="2.5" stroke-linecap="round" />
            </svg>
        </div>
        <div class="ludo-nav-circle">
            <img src="{{ asset('assets/ludo/nav-wallet.svg') }}" alt="Wallet" class="ludo-nav-icon">
        </div>
        <span class="ludo-nav-label">Wallet</span>
    </a>

    {{-- 2. Refer --}}
    <a href="{{ url('/refer') }}" class="ludo-nav-item {{ $isRefer ? 'active' : '' }}" title="Refer & Earn" data-tab="refer">
        <div class="ludo-nav-curly-arc" aria-hidden="true">
            <svg viewBox="0 0 84 22" preserveAspectRatio="none">
                <path d="M 0,1 C 18,1 25,20 42,20 C 59,20 66,1 84,1" fill="none" stroke="#00d2ff" stroke-width="2.5" stroke-linecap="round" />
            </svg>
        </div>
        <div class="ludo-nav-circle">
            <img src="{{ asset('assets/ludo/nav-refer.svg') }}" alt="Refer" class="ludo-nav-icon">
        </div>
        <span class="ludo-nav-label">Refer</span>
    </a>

    {{-- 3. Home --}}
    <a href="{{ url('/') }}" class="ludo-nav-item {{ $isHome ? 'active' : '' }}" title="Home Battles" data-tab="home">
        <div class="ludo-nav-curly-arc" aria-hidden="true">
            <svg viewBox="0 0 84 22" preserveAspectRatio="none">
                <path d="M 0,1 C 18,1 25,20 42,20 C 59,20 66,1 84,1" fill="none" stroke="#00d2ff" stroke-width="2.5" stroke-linecap="round" />
            </svg>
        </div>
        <div class="ludo-nav-circle">
            <img src="{{ asset('assets/ludo/nav-home.svg') }}" alt="Home" class="ludo-nav-icon">
        </div>
        <span class="ludo-nav-label">Home</span>
    </a>

    {{-- 4. Support --}}
    <a href="{{ url('/support') }}" class="ludo-nav-item {{ $isSupport ? 'active' : '' }}" title="Support Center" data-tab="support">
        <div class="ludo-nav-curly-arc" aria-hidden="true">
            <svg viewBox="0 0 84 22" preserveAspectRatio="none">
                <path d="M 0,1 C 18,1 25,20 42,20 C 59,20 66,1 84,1" fill="none" stroke="#00d2ff" stroke-width="2.5" stroke-linecap="round" />
            </svg>
        </div>
        <div class="ludo-nav-circle">
            <img src="{{ asset('assets/ludo/nav-support.svg') }}" alt="Support" class="ludo-nav-icon">
        </div>
        <span class="ludo-nav-label">Support</span>
    </a>

    {{-- 5. Profile --}}
    <a href="{{ url('/profile') }}" class="ludo-nav-item {{ $isProfile ? 'active' : '' }}" title="My Profile" data-tab="profile">
        <div class="ludo-nav-curly-arc" aria-hidden="true">
            <svg viewBox="0 0 84 22" preserveAspectRatio="none">
                <path d="M 0,1 C 18,1 25,20 42,20 C 59,20 66,1 84,1" fill="none" stroke="#00d2ff" stroke-width="2.5" stroke-linecap="round" />
            </svg>
        </div>
        <div class="ludo-nav-circle">
            <img src="{{ asset('assets/ludo/nav-profile.svg') }}" alt="Profile" class="ludo-nav-icon">
        </div>
        <span class="ludo-nav-label">Profile</span>
    </a>
</nav>
