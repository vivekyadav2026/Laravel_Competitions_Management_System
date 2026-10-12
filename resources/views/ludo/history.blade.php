@extends('layouts.player')

@section('title', 'Royal Ludo — History')

@section('content')
    {{-- History Header with Cyan Gradient Lines (Screenshot #2 Reference) --}}
    <div class="ludo-history-header">
        <span class="ludo-history-line"></span>
        <h2 class="ludo-history-title mb-0">&#10024; History &#10024;</h2>
        <span class="ludo-history-line"></span>
    </div>

    {{-- 3 Navigation Tabs --}}
    <div class="ludo-history-tabs">
        <button type="button" class="ludo-history-tab-btn" data-target="historyGameTab">
            <span>&#127918;</span> Game
        </button>
        <button type="button" class="ludo-history-tab-btn active" data-target="historyTxTab">
            <span>&#128176;</span> Transaction
        </button>
        <button type="button" class="ludo-history-tab-btn" data-target="historyReferralTab">
            <span>&#127873;</span> Referral
        </button>
    </div>

    {{-- TAB 1: Transactions (Active in Reference Screenshot #2) --}}
    <div id="historyTxTab" class="history-tab-pane">
        {{-- Card 1: Failed Deposit --}}
        <div class="ludo-history-card">
            <div class="ludo-history-card-status status-failed">
                FAILED
            </div>
            <div class="ludo-history-card-body">
                <div class="ludo-history-info-left">
                    <div class="ludo-history-info-row">
                        <span class="label">Status:</span>
                        <span class="text-danger fw-bold">FAILED</span>
                    </div>
                    <div class="ludo-history-info-row">
                        <span class="label">Request Type:</span>
                        <span>deposit</span>
                    </div>
                    <div class="ludo-history-expiry-text">
                        Auto expired after 5 minutes<br>(No payment received)
                    </div>
                </div>

                {{-- Coin Badge (x) 🪙 100 --}}
                <div class="ludo-history-badge">
                    <span class="text-danger fw-bold fs-6">(x)</span>
                    <span class="fs-6">&#129689;</span>
                    <span class="fs-6">100</span>
                </div>
            </div>
            <div class="ludo-history-card-footer">
                <span>&#128197;</span> 12 Oct 2026 @ 6:08 PM
            </div>
        </div>

        {{-- Card 2: Failed Deposit (As shown in screenshot list) --}}
        <div class="ludo-history-card">
            <div class="ludo-history-card-status status-failed">
                FAILED
            </div>
            <div class="ludo-history-card-body">
                <div class="ludo-history-info-left">
                    <div class="ludo-history-info-row">
                        <span class="label">Status:</span>
                        <span class="text-danger fw-bold">FAILED</span>
                    </div>
                    <div class="ludo-history-info-row">
                        <span class="label">Request Type:</span>
                        <span>deposit</span>
                    </div>
                    <div class="ludo-history-expiry-text">
                        Auto expired after 5 minutes<br>(No payment received)
                    </div>
                </div>

                <div class="ludo-history-badge">
                    <span class="text-danger fw-bold fs-6">(x)</span>
                    <span class="fs-6">&#129689;</span>
                    <span class="fs-6">100</span>
                </div>
            </div>
            <div class="ludo-history-card-footer">
                <span>&#128197;</span> 12 Oct 2026 @ 6:08 PM
            </div>
        </div>

        {{-- Card 3: Sample Successful Deposit --}}
        <div class="ludo-history-card" style="border-color: #22c55e; box-shadow: 0 0 15px rgba(34, 197, 94, 0.25);">
            <div class="ludo-history-card-status status-success">
                SUCCESS
            </div>
            <div class="ludo-history-card-body">
                <div class="ludo-history-info-left">
                    <div class="ludo-history-info-row">
                        <span class="label">Status:</span>
                        <span class="text-success fw-bold">SUCCESS</span>
                    </div>
                    <div class="ludo-history-info-row">
                        <span class="label">Request Type:</span>
                        <span>deposit</span>
                    </div>
                    <div class="ludo-history-expiry-text text-success">
                        UPI payment verified & credited
                    </div>
                </div>

                <div class="ludo-history-badge">
                    <span class="text-success fw-bold fs-6">&#10003;</span>
                    <span class="fs-6">&#129689;</span>
                    <span class="fs-6">250</span>
                </div>
            </div>
            <div class="ludo-history-card-footer">
                <span>&#128197;</span> 11 Oct 2026 @ 2:30 PM
            </div>
        </div>
    </div>

    {{-- TAB 2: Game Battles --}}
    <div id="historyGameTab" class="history-tab-pane d-none">
        <div class="ludo-history-card" style="border-color: #3b82f6;">
            <div class="ludo-history-card-status" style="background: linear-gradient(90deg, #1d4ed8, #3b82f6);">
                MATCH COMPLETED
            </div>
            <div class="ludo-history-card-body">
                <div class="ludo-history-info-left">
                    <div class="ludo-history-info-row">
                        <span class="label">Battle #8491:</span>
                        <span class="text-info fw-bold">Ludo Classic 1v1</span>
                    </div>
                    <div class="ludo-history-info-row">
                        <span class="label">Opponent:</span>
                        <span>Vikram_King</span>
                    </div>
                    <div class="ludo-history-expiry-text text-warning">
                        Room Code: 83920194
                    </div>
                </div>
                <div class="ludo-history-badge">
                    <span class="text-success fw-bold fs-6">+</span>
                    <span class="fs-6">&#129689;</span>
                    <span class="fs-6">95</span>
                </div>
            </div>
            <div class="ludo-history-card-footer">
                <span>&#128197;</span> 11 Oct 2026 @ 9:15 PM
            </div>
        </div>

        <x-ludo.empty-state 
            title="No More Battle Records"
            message="Join more matches in the battle arena to rank up and view your performance stats!"
            actionText="Play Ludo Now"
            actionLink="{{ url('/battles') }}"
        />
    </div>

    {{-- TAB 3: Referral Earnings --}}
    <div id="historyReferralTab" class="history-tab-pane d-none">
        <div class="ludo-history-card" style="border-color: #f59e0b;">
            <div class="ludo-history-card-status" style="background: linear-gradient(90deg, #d97706, #f59e0b);">
                COMMISSION EARNED
            </div>
            <div class="ludo-history-card-body">
                <div class="ludo-history-info-left">
                    <div class="ludo-history-info-row">
                        <span class="label">Referred Player:</span>
                        <span class="text-white fw-bold">Rahul_99</span>
                    </div>
                    <div class="ludo-history-info-row">
                        <span class="label">Type:</span>
                        <span>3% Lifetime Game Win Commission</span>
                    </div>
                    <div class="ludo-history-expiry-text text-success">
                        Credited to Referral Balance
                    </div>
                </div>
                <div class="ludo-history-badge">
                    <span class="text-success fw-bold fs-6">+</span>
                    <span class="fs-6">&#8377;</span>
                    <span class="fs-6">15.00</span>
                </div>
            </div>
            <div class="ludo-history-card-footer">
                <span>&#128197;</span> 10 Oct 2026 @ 4:40 PM
            </div>
        </div>

        <div class="text-center py-3">
            <a href="{{ url('/refer') }}" class="btn btn-outline-info rounded-pill px-4 fw-bold btn-sm">
                <i class="fa-solid fa-gift me-1"></i> Open Referral Dashboard
            </a>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.ludo-history-tab-btn');
            const tabPanes = document.querySelectorAll('.history-tab-pane');

            tabButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    tabButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const targetId = this.dataset.target;
                    tabPanes.forEach(pane => {
                        if (pane.id === targetId) {
                            pane.classList.remove('d-none');
                        } else {
                            pane.classList.add('d-none');
                        }
                    });
                });
            });
        });
    </script>
    @endpush
@endsection
