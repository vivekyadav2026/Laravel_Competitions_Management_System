@props([
    'coins' => 0,
    'referral' => '0.00'
])

<div class="ludo-header-balances">
    {{-- Coins Balance Card --}}
    <a href="{{ url('/wallet') }}" class="ludo-balance-card ludo-balance-coins" title="View Coins Wallet">
        <img src="{{ asset('assets/ludo/coins-sack.svg') }}" alt="Coins" class="ludo-balance-icon">
        <div class="ludo-balance-info">
            <span class="ludo-balance-amount">{{ $coins }}</span>
            <span class="ludo-balance-label">Coins</span>
        </div>
    </a>

    {{-- Referral Balance Card --}}
    <a href="{{ url('/refer') }}" class="ludo-balance-card ludo-balance-referral" title="View Referral Earnings">
        <img src="{{ asset('assets/ludo/referral-cash.svg') }}" alt="Referral" class="ludo-balance-icon">
        <div class="ludo-balance-info">
            <span class="ludo-balance-amount">{{ number_format((float)$referral, 2) }}</span>
            <span class="ludo-balance-label">Referral</span>
        </div>
    </a>
</div>
