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
    <title>@yield('title', config('ludo.brand_name', 'Fsaludoclub') . ' — ' . config('ludo.brand_tagline', 'Play Ludo • Win Glory'))</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/fsaludoclub-icon.png') }}">

    {{-- Local Bootstrap 5 --}}
    <link rel="stylesheet" href="{{ versioned_asset('css/bootstrap.min.css') }}">
    
    {{-- FontAwesome 6 (CDN) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Google Fonts: Poppins & Cinzel --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Ludo Core Theme --}}
    <link rel="stylesheet" href="{{ versioned_asset('css/ludo-theme.css') }}">

    @stack('styles')
</head>
<body>

    <div class="ludo-desktop-wrapper">
        {{-- Left / Centered Mobile App Container --}}
        <div class="ludo-mobile-frame">
            @php
                $headerCoins = auth()->check() ? auth()->user()->fresh()->coins : ($coins ?? 0);
                $headerReferral = auth()->check() ? auth()->user()->fresh()->referral_balance : ($referral ?? '0.00');
            @endphp
            {{-- Navigation Drawer --}}
            <x-ludo.drawer :coins="$headerCoins" :referral="$headerReferral" />

            {{-- Top Sticky Header --}}
            <x-ludo.header :coins="$headerCoins" :referral="$headerReferral" />

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
    </div>

    {{-- Bootstrap JS --}}
    <script src="{{ versioned_asset('js/bootstrap.bundle.min.js') }}"></script>

    {{-- Ludo Interactive App Script --}}
    <script src="{{ versioned_asset('js/ludo-app.js') }}"></script>

    {{-- BFCache Invalidation: Force reload when page is restored from browser back/forward history cache --}}
    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
