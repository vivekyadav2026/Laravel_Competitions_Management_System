@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Home Lobby')

@section('content')
    {{-- Announcement Notice Banner (Synchronized with Admin CMS) --}}
    <x-ludo.announcement>
        {!! $announcement ?? '<span class="fw-bold">⭐ 🎉 📢 Fsaludoclub परिवार में आप सभी प्लेयरों का स्वागत है!</span> किसी भी प्लेयर के साथ ना कभी गलत होगा ना ही कभी गलत होने देगा 🙏🙏 विश्वास बनाए रखें 🙏🙏' !!}
    </x-ludo.announcement>

    {{-- Gold Tournament Category Banner --}}
    <x-ludo.gold-banner 
        title="OUR TOURNAMENTS" 
        subtitle="Choose your battle and win big" 
        link="{{ url('/tournaments') }}" 
    />

    {{-- 2-Column Game Selection Cards Grid --}}
    <div class="ludo-game-grid">
        {{-- Card 1: Ludo Classic --}}
        <a href="{{ url('/battles') }}" class="ludo-game-card ludo-card-ludo" aria-label="Play Fsaludoclub Classic Battles">
            <div class="ludo-border-beam-glow"></div>
            <div class="ludo-border-beam"></div>
            <span class="ludo-badge-live">
                <span class="live-dot"></span> LIVE
            </span>
            <div class="ludo-card-body">
                <img src="{{ asset('assets/ludo/ludo-board.svg') }}" alt="Fsaludoclub Classic">
            </div>
        </a>

        {{-- Card 2: 24x7 Ludo Support --}}
        <a href="{{ url('/support') }}" class="ludo-game-card ludo-card-support" aria-label="24x7 Ludo Support">
            <div class="ludo-border-beam-glow"></div>
            <div class="ludo-border-beam"></div>
            <span class="ludo-badge-live ludo-badge-gold">
                <span class="live-dot"></span> LIVE
            </span>
            <div class="ludo-card-body">
                <img src="{{ asset('assets/ludo/support-card.svg') }}" alt="Ludo Support 24x7">
            </div>
        </a>
    </div>

    {{-- Feature Banner: Fastest Withdrawal & 24*7 Support --}}
    <div class="ludo-feature-banner">
        <a href="{{ url('/wallet') }}">
            <img src="{{ asset('assets/ludo/withdrawal-banner.svg') }}" alt="Fastest Withdrawal and Support Service 24*7">
        </a>
    </div>

    {{-- Quick Action Cards: Install App & Dark Mode --}}
    <div class="ludo-quick-actions">
        {{-- Install App Card --}}
        <div class="ludo-quick-card js-install-app">
            <div class="ludo-quick-icon">
                <i class="fa-solid fa-mobile-screen-button"></i>
            </div>
            <div>
                <div class="ludo-quick-title">Install App</div>
                <div class="ludo-quick-sub">Fast access</div>
            </div>
        </div>

        {{-- Dark Mode Toggle Card --}}
        <div class="ludo-quick-card js-ludo-theme-toggle">
            <div class="ludo-quick-icon">
                <i class="fa-solid fa-moon text-warning"></i>
            </div>
            <div>
                <div class="ludo-quick-title">Dark Mode</div>
                <div class="ludo-quick-sub">Change Theme</div>
            </div>
        </div>
    </div>
@endsection
