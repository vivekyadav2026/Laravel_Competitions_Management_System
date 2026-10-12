@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — My Wallet & Points')

@section('content')
    {{-- Non-Cash Regulatory Notice Banner --}}
    <x-ludo.announcement>
        <span class="fw-bold">ℹ️ वर्चुअल पॉइंट्स सूचना:</span> यह प्लेटफार्म केवल गैर-नकद (Non-Cash), गैर-हस्तांतरणीय (Non-Transferable) वर्चुअल टूर्नामेंट पॉइंट्स पर आधारित है। वास्तविक धन (Real-Money) या नकद निकासी वर्तमान में पूरी तरह अक्षम (Disabled) है।
    </x-ludo.announcement>

    {{-- Choose Amount To Add Card (Screenshot #1 Reference) --}}
    <div class="ludo-amount-card mb-4">
        <h2 class="ludo-amount-title">Choose Amount To Add 💰</h2>

        <form action="{{ route('ludo.wallet.deposit') }}" method="POST" id="walletDepositForm">
            @csrf

            {{-- Enter Amount Input Box --}}
            <div class="ludo-amount-input-box">
                <div class="ludo-rupee-circle">&#8377;</div>
                <div class="ludo-amount-input-meta">
                    <div class="ludo-amount-label">ENTER AMOUNT (VIRTUAL POINTS)</div>
                    <input type="number" name="amount" id="ludoDepositAmount" class="ludo-amount-input-field @error('amount') is-invalid @enderror" value="{{ old('amount', 100) }}" min="50" max="50000" step="50" required>
                </div>
            </div>

            @error('amount')
                <div class="text-danger small mb-3 fw-semibold">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}
                </div>
            @enderror

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
            <button type="submit" class="ludo-btn-gold-next" id="btnDepositNext">
                NEXT &rarr;
            </button>
        </form>
    </div>

    {{-- Total Balance Summary Card --}}
    <div class="ludo-refer-container p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <span class="text-secondary small d-block">TOTAL VIRTUAL POINTS</span>
                <h3 class="fw-bold text-white mb-0">🪙 {{ number_format($coins ?? 0) }} <span class="fs-6 text-info">Points</span></h3>
            </div>
            <img src="{{ asset('assets/ludo/coins-sack.svg') }}" alt="Coins" style="width: 48px; height: 48px;">
        </div>

        <div class="row g-2 text-center">
            <div class="col-6">
                <div class="p-2 rounded-3" style="background: rgba(14, 42, 92, 0.7); border: 1px solid #1d4ed8;">
                    <div class="text-secondary small" style="font-size: 10px;">AVAILABLE TOURNAMENT POINTS</div>
                    <div class="fw-bold text-white fs-6">🪙 {{ number_format($coins ?? 0) }}</div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 rounded-3" style="background: rgba(67, 27, 92, 0.7); border: 1px solid #7e22ce;">
                    <div class="text-secondary small" style="font-size: 10px;">REFERRAL COMMISSION POINTS</div>
                    <div class="fw-bold text-success fs-6">🪙 {{ number_format($referral ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Regulatory Disabled Real-Money Withdrawal Notice Card --}}
    <div class="ludo-create-battle-card mb-4" style="border-color: rgba(234, 179, 8, 0.3);">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-bold text-white mb-0 fs-6">
                <i class="fa-solid fa-lock text-warning me-1"></i> Cash Payouts & Redemptions
            </h5>
            <span class="badge bg-danger text-white" style="font-size: 9px;">DISABLED PENDING APPROVAL</span>
        </div>
        <p class="small text-secondary mb-3">
            Real-money cash withdrawals and gateway settlements are intentionally disabled under fair play and regulatory guidelines. Virtual tournament points hold zero real-world cash equivalence.
        </p>
        <button type="button" class="btn btn-outline-secondary w-100 rounded-3 fw-bold py-2 disabled" style="opacity: 0.5; cursor: not-allowed;">
            <i class="fa-solid fa-ban me-1"></i> Cash Withdrawals Unavailable
        </button>
    </div>

    {{-- Recent Transaction Ledger Cards (Pending, Successful, Failed, Expired) --}}
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-white mb-0 fs-6">
                <i class="fa-solid fa-receipt text-info me-1"></i> Transaction History
            </h5>
            <a href="{{ url('/history') }}" class="small text-info fw-bold text-decoration-none" style="font-size: 11px;">
                View All &rarr;
            </a>
        </div>

        @forelse($transactions as $tx)
            @php
                $statusClass = match($tx->status) {
                    'successful' => 'status-success',
                    'pending' => 'bg-warning text-dark',
                    'expired' => 'bg-secondary text-white',
                    default => 'status-failed'
                };
                $borderColor = match($tx->status) {
                    'successful' => 'rgba(34, 197, 94, 0.5)',
                    'pending' => 'rgba(234, 179, 8, 0.5)',
                    default => 'rgba(239, 68, 68, 0.5)'
                };
            @endphp
            <div class="ludo-history-card mb-2" style="border-color: {{ $borderColor }};">
                <div class="ludo-history-card-status {{ $statusClass }}">
                    {{ strtoupper($tx->status) }}
                </div>
                <div class="ludo-history-card-body">
                    <div class="ludo-history-info-left">
                        <div class="ludo-history-info-row">
                            <span class="label">Type:</span>
                            <span class="text-white text-capitalize">{{ str_replace('_', ' ', $tx->type) }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Ref:</span>
                            <span class="text-info font-monospace">{{ $tx->tx_uid }}</span>
                        </div>
                        <div class="small text-secondary" style="font-size: 11px;">
                            {{ $tx->description }}
                        </div>
                    </div>

                    <div class="ludo-history-badge">
                        <span class="{{ $tx->points_delta >= 0 ? 'text-success' : 'text-danger' }} fw-bold fs-6">
                            {{ $tx->points_delta >= 0 ? '+' : '' }}{{ number_format($tx->points_delta) }}
                        </span>
                        <span class="fs-6">&#129689;</span>
                    </div>
                </div>
                <div class="ludo-history-card-footer justify-content-between">
                    <span>&#128197; {{ $tx->created_at ? $tx->created_at->format('d M Y @ h:i A') : 'Recent' }}</span>
                    <span>Bal After: 🪙 {{ number_format($tx->balance_after) }}</span>
                </div>
            </div>
        @empty
            <x-ludo.empty-state 
                title="No Transactions Recorded"
                message="You haven't made any point reloads or match entries yet. Choose an amount above to reload your tournament points."
            />
        @endforelse

        @if($transactions->hasPages())
            <div class="d-flex justify-content-center my-3 ludo-pagination-wrapper">
                {{ $transactions->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('ludoDepositAmount');
            const pills = document.querySelectorAll('.ludo-amount-pill');

            pills.forEach(pill => {
                pill.addEventListener('click', function() {
                    pills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    if (amountInput) {
                        amountInput.value = this.dataset.amount;
                    }
                });
            });

            const depositForm = document.getElementById('walletDepositForm');
            const submitBtn = document.getElementById('btnDepositNext');
            if (depositForm && submitBtn) {
                depositForm.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing Reload...';
                });
            }
        });
    </script>
    @endpush
@endsection
