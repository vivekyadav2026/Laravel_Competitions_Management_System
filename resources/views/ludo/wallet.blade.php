@extends('layouts.player')

@section('title', 'Royal Ludo — Choose Amount To Add')

@section('content')
    {{-- Choose Amount To Add Card (Screenshot #1 Reference) --}}
    <div class="ludo-amount-card mb-4">
        <h2 class="ludo-amount-title">Choose Amount To Add 💰</h2>

        {{-- Enter Amount Input Box --}}
        <div class="ludo-amount-input-box">
            <div class="ludo-rupee-circle">&#8377;</div>
            <div class="ludo-amount-input-meta">
                <div class="ludo-amount-label">ENTER AMOUNT</div>
                <input type="number" id="ludoDepositAmount" class="ludo-amount-input-field" value="100" min="50" step="50">
            </div>
        </div>

        {{-- 8 Amount Pills Grid --}}
        <div class="ludo-amount-grid">
            <button type="button" class="ludo-amount-pill active" data-amount="100">&#8377;100</button>
            <button type="button" class="ludo-amount-pill" data-amount="250">&#8377;250</button>
            <button type="button" class="ludo-amount-pill" data-amount="500">&#8377;500</button>
            <button type="button" class="ludo-amount-pill" data-amount="2000">&#8377;2000</button>
            <button type="button" class="ludo-amount-pill" data-amount="5000">&#8377;5000</button>
            <button type="button" class="ludo-amount-pill" data-amount="10000">&#8377;10000</button>
            <button type="button" class="ludo-amount-pill" data-amount="15000">&#8377;15000</button>
            <button type="button" class="ludo-amount-pill" data-amount="20000">&#8377;20000</button>
        </div>

        {{-- Bright Gold NEXT Button --}}
        <button type="button" class="ludo-btn-gold-next" id="btnDepositNext">
            NEXT &rarr;
        </button>
    </div>

    {{-- Total Balance Summary Card --}}
    <div class="ludo-refer-container p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <span class="text-secondary small d-block">TOTAL WALLET BALANCE</span>
                <h3 class="fw-bold text-white mb-0">🪙 {{ $coins ?? 0 }} <span class="fs-6 text-info">Coins</span></h3>
            </div>
            <img src="{{ asset('assets/ludo/coins-sack.svg') }}" alt="Coins" style="width: 48px; height: 48px;">
        </div>

        <div class="row g-2 text-center">
            <div class="col-6">
                <div class="p-2 rounded-3" style="background: rgba(14, 42, 92, 0.7); border: 1px solid #1d4ed8;">
                    <div class="text-secondary small" style="font-size: 10px;">DEPOSIT COINS</div>
                    <div class="fw-bold text-white fs-6">₹{{ $coins ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 rounded-3" style="background: rgba(67, 27, 92, 0.7); border: 1px solid #7e22ce;">
                    <div class="text-secondary small" style="font-size: 10px;">WINNING CASH</div>
                    <div class="fw-bold text-success fs-6">₹0.00</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Instant Withdrawal Section --}}
    <div class="ludo-create-battle-card mb-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-bold text-white mb-0 fs-6">
                <i class="fa-solid fa-arrow-up-right-from-square text-warning me-1"></i> Instant Payouts (UPI / Bank)
            </h5>
            <span class="badge bg-warning text-dark" style="font-size: 9px;">24x7</span>
        </div>
        <p class="small text-secondary mb-3">Minimum withdrawal amount is ₹100. Payouts processed within 5-10 minutes directly to your linked UPI ID or Bank Account.</p>
        
        <button type="button" class="btn btn-outline-warning w-100 rounded-3 fw-bold py-2" onclick="showLudoToast('Withdrawal requires minimum winning balance of ₹100.')">
            Request Payout
        </button>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('ludoDepositAmount');
            const pills = document.querySelectorAll('.ludo-amount-pill');
            const nextBtn = document.getElementById('btnDepositNext');

            pills.forEach(pill => {
                pill.addEventListener('click', function() {
                    pills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    if (amountInput) {
                        amountInput.value = this.dataset.amount;
                    }
                });
            });

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    const amt = amountInput ? amountInput.value : 100;
                    showLudoToast('Gateway ready: Selected ₹' + amt + '. Proceeding to secure UPI payment...');
                });
            }
        });
    </script>
    @endpush
@endsection
