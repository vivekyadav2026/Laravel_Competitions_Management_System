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

<nav class="ludo-bottom-nav">
    {{-- 1. Wallet --}}
    <a href="{{ url('/wallet') }}" class="ludo-nav-item {{ $isWallet ? 'active' : '' }}" title="My Wallet">
        <img src="{{ asset('assets/ludo/nav-wallet.svg') }}" alt="Wallet" class="ludo-nav-icon">
        <span class="ludo-nav-label">Wallet</span>
    </a>

    {{-- 2. Refer --}}
    <a href="{{ url('/refer') }}" class="ludo-nav-item {{ $isRefer ? 'active' : '' }}" title="Refer & Earn">
        <img src="{{ asset('assets/ludo/nav-refer.svg') }}" alt="Refer" class="ludo-nav-icon">
        <span class="ludo-nav-label">Refer</span>
    </a>

    {{-- 3. Home --}}
    <a href="{{ url('/play') }}" class="ludo-nav-item {{ $isHome ? 'active' : '' }}" title="Home Battles">
        <img src="{{ asset('assets/ludo/nav-home.svg') }}" alt="Home" class="ludo-nav-icon">
        <span class="ludo-nav-label">Home</span>
    </a>

    {{-- 4. Support --}}
    <a href="{{ url('/support') }}" class="ludo-nav-item {{ $isSupport ? 'active' : '' }}" title="Support Center">
        <img src="{{ asset('assets/ludo/nav-support.svg') }}" alt="Support" class="ludo-nav-icon">
        <span class="ludo-nav-label">Support</span>
    </a>

    {{-- 5. Profile --}}
    <a href="{{ url('/profile') }}" class="ludo-nav-item {{ $isProfile ? 'active' : '' }}" title="My Profile">
        <img src="{{ asset('assets/ludo/nav-profile.svg') }}" alt="Profile" class="ludo-nav-icon">
        <span class="ludo-nav-label">Profile</span>
    </a>
</nav>
