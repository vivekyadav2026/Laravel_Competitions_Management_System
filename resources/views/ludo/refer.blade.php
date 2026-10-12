@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Refer & Earn')

@section('content')
    <div class="ludo-refer-container">
        {{-- Overflowing Treasure Chest Top Graphic --}}
        <img src="{{ asset('assets/ludo/treasure-chest.svg') }}" alt="Treasure Chest Rewards" class="ludo-refer-chest-banner">

        {{-- Exclusive Rewards Tier Card --}}
        <div class="ludo-refer-reward-card">
            <div class="ludo-reward-badge">
                <i class="fa-solid fa-coins text-warning"></i>
                <span class="fw-bold">Invite Friends & Earn</span>
            </div>
            <div class="ludo-reward-headline">
                Exclusive Rewards! 💎
            </div>

            <div class="ludo-reward-table">
                <div class="ludo-reward-row">
                    <span class="text-light">1 Eligible Referral</span>
                    <span class="text-warning fw-bold">+50 🪙 Virtual Points</span>
                </div>
                <div class="ludo-reward-row">
                    <span class="text-light">Tournament Commission</span>
                    <span class="text-success fw-bold">{{ $commission ?? '3%' }} Lifetime Points</span>
                </div>
                <div class="ludo-reward-row">
                    <span class="text-light">Total Referrals</span>
                    <span class="text-info fw-bold">{{ $totalReferrals ?? 0 }}</span>
                </div>
                <div class="ludo-reward-row">
                    <span class="text-light">Total Eligible Rewards</span>
                    <span class="text-warning fw-bold">🪙 {{ number_format($totalEligibleRewards ?? 0) }} Points</span>
                </div>
            </div>
        </div>

        {{-- Neon Unique Referral Code Box --}}
        <div class="ludo-code-box">
            <div class="ludo-code-box-title">
                🎁 YOUR UNIQUE REFERRAL CODE
            </div>
            <div class="ludo-code-display">
                <span id="ludoReferralCode" class="ludo-code-text">{{ $referralCode }}</span>
                <button type="button" class="ludo-btn-copy js-copy-code" data-target="ludoReferralCode">
                    <i class="fa-regular fa-copy"></i> Copy
                </button>
            </div>
            <div class="text-center mt-2">
                <small class="text-secondary" style="font-size: 10px;">
                    Link: <span class="text-info text-break">{{ $shareUrl }}</span>
                </small>
            </div>
        </div>

        {{-- Action Buttons: Web Share API + WhatsApp Invite --}}
        <div class="d-flex flex-column gap-2 mb-4">
            <button type="button" class="ludo-btn-invite" id="btnWebShare" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                <i class="fa-solid fa-share-nodes fs-5"></i>
                <span>Invite Friends (Share Link)</span>
            </button>

            <a href="https://api.whatsapp.com/send?text={{ urlencode('Play ' . config('ludo.brand_name', 'Fsaludoclub') . ' with me! Use my referral code ' . $referralCode . ' to get 100 bonus tournament points: ' . $shareUrl) }}" 
               target="_blank" 
               class="ludo-btn-invite">
                <i class="fa-brands fa-whatsapp fs-4"></i>
                <span>Share via WhatsApp</span>
            </a>
        </div>

        {{-- Referral History Section --}}
        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-white mb-0 fs-6">
                    <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> Referral History
                </h6>
                <span class="badge bg-dark text-info border border-info border-opacity-50" style="font-size: 9.5px;">
                    {{ $totalReferrals }} Friends Joined
                </span>
            </div>

            @forelse($referrals as $ref)
                <div class="ludo-history-card mb-2" style="border-color: rgba(0, 210, 255, 0.3);">
                    <div class="p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-white small">
                                <i class="fa-solid fa-user-check text-success me-1"></i>
                                {{ $ref->referredUser->name ?? 'User #' . $ref->referred_user_id }}
                            </div>
                            <small class="text-secondary" style="font-size: 10px;">
                                Joined {{ $ref->created_at ? $ref->created_at->format('d M Y, h:i A') : 'Recently' }}
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50" style="font-size: 10px;">
                                +{{ $ref->reward_points }} 🪙
                            </span>
                            <div class="text-secondary small" style="font-size: 9px; text-transform: uppercase;">
                                {{ $ref->status }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <x-ludo.empty-state 
                    title="No Referrals Yet"
                    message="Share your unique referral code or link with friends to earn free tournament points and lifetime commissions!"
                />
            @endforelse

            @if($referrals->hasPages())
                <div class="d-flex justify-content-center mt-3 ludo-pagination-wrapper">
                    {{ $referrals->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const shareBtn = document.getElementById('btnWebShare');
            const shareTitle = '{{ config("ludo.brand_name", "Fsaludoclub") }}';
            const shareText = 'Play {{ config("ludo.brand_name", "Fsaludoclub") }} with me! Use my referral code {{ $referralCode }} to get bonus points:';
            const shareUrl = '{{ $shareUrl }}';

            if (shareBtn) {
                shareBtn.addEventListener('click', async function() {
                    if (navigator.share) {
                        try {
                            await navigator.share({
                                title: shareTitle,
                                text: shareText,
                                url: shareUrl,
                            });
                        } catch (err) {
                            if (err.name !== 'AbortError') {
                                copyToClipboard(shareUrl, 'Referral link copied to clipboard!');
                            }
                        }
                    } else {
                        // Fallback copy
                        copyToClipboard(shareUrl, 'Referral link copied to clipboard!');
                    }
                });
            }

            function copyToClipboard(text, successMsg) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function() {
                        showLudoToast(successMsg);
                    }).catch(function() {
                        fallbackCopy(text, successMsg);
                    });
                } else {
                    fallbackCopy(text, successMsg);
                }
            }

            function fallbackCopy(text, successMsg) {
                const tempInput = document.createElement('input');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                showLudoToast(successMsg);
            }
        });
    </script>
    @endpush
@endsection
