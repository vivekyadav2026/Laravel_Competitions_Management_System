@props([
    'title' => 'OUR TOURNAMENTS',
    'subtitle' => 'Choose your battle and win big',
    'link' => '#',
    'image' => null,
])

@php
    $bannerImg = $image;
    if (!$bannerImg) {
        $cleanTitle = strtoupper(trim($title));
        if ($cleanTitle === 'RUNNING BATTLES') {
            $bannerImg = versioned_asset('assets/ludo/banner-running-battles.jpg');
        } elseif ($cleanTitle === 'OUR TOURNAMENTS') {
            $bannerImg = versioned_asset('assets/ludo/banner-our-tournaments.jpg');
        }
    }
@endphp

<a href="{{ $link }}" class="ludo-gold-banner text-decoration-none d-block">
    @if($bannerImg)
        <img src="{{ $bannerImg }}" alt="{{ $title }} - {{ $subtitle }}" class="w-100 h-auto rounded-3 shadow-sm" style="display: block; object-fit: cover;">
    @else
        <div style="background: radial-gradient(circle at center, #1b0f02 0%, #050301 100%); border: 2px solid #ffd700; border-radius: 12px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; position: relative; box-shadow: 0 4px 18px rgba(255, 215, 0, 0.25);">
            {{-- Left crossed swords badge --}}
            <div style="width: 44px; height: 44px; background: #130a01; border: 1.5px solid #ffd700; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(255, 215, 0, 0.4);">
                <i class="fa-solid fa-swords text-warning fs-5">⚔️</i>
            </div>

            {{-- Center Title & Subtitle --}}
            <div class="text-center flex-grow-1 px-2">
                <div style="font-family: 'Cinzel', serif; font-size: 19px; font-weight: 900; color: #ffd700; letter-spacing: 2px; text-shadow: 0 2px 6px rgba(0,0,0,0.8); line-height: 1.2;">
                    {{ $title }}
                </div>
                <div style="font-size: 10.5px; font-weight: 600; color: #fef08a; letter-spacing: 0.5px; margin-top: 2px;">
                    {{ $subtitle }}
                </div>
            </div>

            {{-- Right crossed swords badge --}}
            <div style="width: 44px; height: 44px; background: #130a01; border: 1.5px solid #ffd700; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 10px rgba(255, 215, 0, 0.4);">
                <i class="fa-solid fa-swords text-warning fs-5">⚔️</i>
            </div>
        </div>
    @endif
</a>
