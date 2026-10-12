<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Royal Ludo — Turn Fun Into Funds!')</title>

    {{-- Local Bootstrap 5 --}}
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    {{-- FontAwesome 6 (CDN) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Google Fonts: Poppins & Cinzel --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Ludo Core Theme --}}
    <link rel="stylesheet" href="{{ asset('css/ludo-theme.css') }}">

    @stack('styles')
</head>
<body>

    <div class="ludo-desktop-wrapper">
        {{-- Left / Centered Mobile App Container --}}
        <div class="ludo-mobile-frame">
            {{-- Navigation Drawer --}}
            <x-ludo.drawer :coins="$coins ?? 0" :referral="$referral ?? '0.00'" />

            {{-- Top Sticky Header --}}
            <x-ludo.header :coins="$coins ?? 0" :referral="$referral ?? '0.00'" />

            {{-- Main Content Container (Padded so header & bottom nav never overlap) --}}
            <main class="ludo-page-content">
                {{-- Flash Alerts & Errors --}}
                <x-ludo.alert />

                {{-- Page Main Content Slot --}}
                @yield('content')
            </main>

            {{-- Bottom Sticky Navigation (5 Tabs) --}}
            <x-ludo.bottom-nav :active="$activeTab ?? 'home'" />
        </div>

        {{-- Desktop Showcase Column (Matches the reference screenshots) --}}
        <div class="ludo-desktop-showcase">
            <img src="{{ asset('assets/ludo/logo.svg') }}" alt="Royal Ludo Crest" class="ludo-showcase-logo">
            <h1 class="ludo-showcase-title">Turn Fun Into Funds!</h1>
            <div class="ludo-showcase-subtitle">
                <span>For best experience, open</span>
                <span class="text-white fw-bold">https://royalludo.com</span>
                <span>on</span>
                <i class="fa-brands fa-chrome text-warning fs-5"></i>
                <span class="text-white fw-bold">chrome mobile</span>
            </div>
        </div>
    </div>

    {{-- Bootstrap JS --}}
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>

    {{-- Ludo Interactive App Script --}}
    <script src="{{ asset('js/ludo-app.js') }}"></script>

    @stack('scripts')
</body>
</html>
