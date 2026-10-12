@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — History & Ledger')

@section('content')
    {{-- History Header with Cyan Gradient Lines (Screenshot #2 Reference) --}}
    <div class="ludo-history-header">
        <span class="ludo-history-line"></span>
        <h2 class="ludo-history-title mb-0">&#10024; History &#10024;</h2>
        <span class="ludo-history-line"></span>
    </div>

    {{-- 3 Navigation Tabs --}}
    <div class="ludo-history-tabs">
        <button type="button" class="ludo-history-tab-btn {{ request('tab', 'tx') === 'game' ? 'active' : '' }}" data-target="historyGameTab">
            <span>&#127918;</span> Game
        </button>
        <button type="button" class="ludo-history-tab-btn {{ request('tab', 'tx') === 'tx' ? 'active' : '' }}" data-target="historyTxTab">
            <span>&#128176;</span> Transaction
        </button>
        <button type="button" class="ludo-history-tab-btn {{ request('tab', 'tx') === 'ref' ? 'active' : '' }}" data-target="historyReferralTab">
            <span>&#127873;</span> Referral
        </button>
    </div>

    {{-- =========================================================================
         TAB 1: TRANSACTIONS (Active in Reference Screenshot #2)
         ========================================================================= --}}
    <div id="historyTxTab" class="history-tab-pane {{ request('tab', 'tx') === 'tx' ? '' : 'd-none' }}">
        {{-- Filter Bar --}}
        <div class="d-flex gap-2 mb-3">
            <form action="{{ url('/history') }}" method="GET" class="d-flex gap-2 w-100">
                <input type="hidden" name="tab" value="tx">
                <select name="tx_status" class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-50 rounded-pill small" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="successful" {{ request('tx_status') === 'successful' ? 'selected' : '' }}>Successful</option>
                    <option value="failed" {{ request('tx_status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="pending" {{ request('tx_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="expired" {{ request('tx_status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
                <select name="tx_type" class="form-select form-select-sm bg-dark text-white border-secondary border-opacity-50 rounded-pill small" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="deposit" {{ request('tx_type') === 'deposit' ? 'selected' : '' }}>Deposit</option>
                    <option value="entry_fee" {{ request('tx_type') === 'entry_fee' ? 'selected' : '' }}>Entry Fee</option>
                    <option value="refund" {{ request('tx_type') === 'refund' ? 'selected' : '' }}>Refund</option>
                    <option value="referral_bonus" {{ request('tx_type') === 'referral_bonus' ? 'selected' : '' }}>Referral Bonus</option>
                </select>
            </form>
        </div>

        @forelse($txHistory as $tx)
            @php
                $statusClass = match($tx->status) {
                    'successful' => 'status-success',
                    'pending' => 'bg-warning text-dark',
                    'expired' => 'bg-secondary text-white',
                    default => 'status-failed'
                };
                $cardBorder = match($tx->status) {
                    'successful' => 'border-success',
                    'pending' => 'border-warning',
                    default => 'border-danger'
                };
            @endphp
            <div class="ludo-history-card">
                <div class="ludo-history-card-status {{ $statusClass }}">
                    {{ strtoupper($tx->status) }}
                </div>
                <div class="ludo-history-card-body">
                    <div class="ludo-history-info-left">
                        <div class="ludo-history-info-row">
                            <span class="label">Status:</span>
                            <span class="{{ $tx->status === 'successful' ? 'text-success' : 'text-danger' }} fw-bold text-uppercase">{{ $tx->status }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Request Type:</span>
                            <span class="text-white text-capitalize">{{ str_replace('_', ' ', $tx->type) }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Ref:</span>
                            <span class="text-info font-monospace">{{ $tx->tx_uid }}</span>
                        </div>
                        <div class="ludo-history-expiry-text">
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
                    <span>&#128197; {{ $tx->created_at ? $tx->created_at->format('d M Y @ h:i A') : 'N/A' }}</span>
                    <span>Bal After: 🪙 {{ number_format($tx->balance_after) }}</span>
                </div>
            </div>
        @empty
            <x-ludo.empty-state 
                title="No Transactions Found"
                message="No ledger records matched your selected filter. Reload virtual points from your wallet to begin playing."
            />
        @endforelse

        @if($txHistory->hasPages())
            <div class="d-flex justify-content-center my-3 ludo-pagination-wrapper">
                {{ $txHistory->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- =========================================================================
         TAB 2: GAME HISTORY
         ========================================================================= --}}
    <div id="historyGameTab" class="history-tab-pane {{ request('tab') === 'game' ? '' : 'd-none' }}">
        @forelse($gameHistory as $game)
            @php
                $isCreator = auth()->check() && auth()->id() === $game->creator_id;
                $opponentName = $isCreator ? ($game->joiner->name ?? 'Waiting...') : ($game->creator->name ?? 'Creator');
                $isWinner = auth()->check() && auth()->id() === $game->winner_id;
            @endphp
            <div class="ludo-history-card" style="border-color: #3b82f6;">
                <div class="ludo-history-card-status" style="background: linear-gradient(90deg, #1d4ed8, #3b82f6);">
                    {{ strtoupper($game->status) }}
                </div>
                <div class="ludo-history-card-body">
                    <div class="ludo-history-info-left">
                        <div class="ludo-history-info-row">
                            <span class="label">Match Ref:</span>
                            <span class="text-info font-monospace">{{ $game->battle_uid }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Opponent:</span>
                            <span class="text-white fw-bold">{{ $opponentName }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Claimed Result:</span>
                            <span class="badge bg-dark text-warning border border-warning border-opacity-50 text-uppercase" style="font-size: 9.5px;">
                                {{ $game->claimed_result }}
                            </span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Final Result:</span>
                            <span class="badge {{ $isWinner ? 'bg-success' : 'bg-secondary' }} text-uppercase" style="font-size: 9.5px;">
                                {{ $game->final_result }}
                            </span>
                        </div>
                        <div class="ludo-history-expiry-text">
                            Review Status: <span class="text-white text-capitalize">{{ str_replace('_', ' ', $game->review_status) }}</span>
                            @if($game->room_code)
                                • Room: <strong class="text-warning">{{ $game->room_code }}</strong>
                            @endif
                        </div>
                    </div>
                    <div class="ludo-history-badge">
                        <span class="{{ $isWinner ? 'text-success' : 'text-info' }} fw-bold fs-6">
                            {{ $isWinner ? '+' . number_format($game->prize) : '🪙 ' . number_format($game->entry_fee) }}
                        </span>
                        <span class="fs-6">&#129689;</span>
                    </div>
                </div>
                <div class="ludo-history-card-footer justify-content-between">
                    <span>&#128197; {{ $game->created_at ? $game->created_at->format('d M Y @ h:i A') : 'N/A' }}</span>
                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 10px;" data-bs-toggle="modal" data-bs-target="#gameDetailsModal{{ $game->id }}">
                        View Details
                    </button>
                </div>
            </div>

            {{-- Game Details Modal --}}
            <div class="modal fade" id="gameDetailsModal{{ $game->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered px-3">
                    <div class="modal-content ludo-modal-dialog">
                        <div class="modal-header border-secondary border-opacity-25 pb-2">
                            <h5 class="modal-title fw-bold fs-6">
                                <span class="text-warning">🎮</span> Battle {{ $game->battle_uid }} Details
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-light small py-3">
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                                <li><strong>Creator:</strong> {{ $game->creator->name ?? 'Unknown' }}</li>
                                <li><strong>Challenger:</strong> {{ $game->joiner->name ?? 'Awaiting Opponent' }}</li>
                                <li><strong>Entry Fee:</strong> 🪙 {{ number_format($game->entry_fee) }} Points</li>
                                <li><strong>Prize Pool:</strong> 🪙 {{ number_format($game->prize) }} Points</li>
                                <li><strong>Ludo King Room Code:</strong> {{ $game->room_code ?? 'Not Generated' }}</li>
                                <li><strong>Review Status:</strong> {{ ucwords(str_replace('_', ' ', $game->review_status)) }}</li>
                                <li><strong>Date Started:</strong> {{ $game->created_at ? $game->created_at->format('d M Y @ h:i:s A') : 'N/A' }}</li>
                            </ul>
                        </div>
                        <div class="modal-footer border-secondary border-opacity-25 pt-2">
                            <button type="button" class="btn btn-sm btn-info rounded-pill px-4 text-dark fw-bold" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <x-ludo.empty-state 
                title="No Game History"
                message="You haven't participated in any completed battles yet. Join a live match to view game results!"
                actionText="Play Ludo Now"
                actionLink="{{ url('/battles') }}"
            />
        @endforelse

        @if($gameHistory->hasPages())
            <div class="d-flex justify-content-center my-3 ludo-pagination-wrapper">
                {{ $gameHistory->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- =========================================================================
         TAB 3: REFERRAL HISTORY
         ========================================================================= --}}
    <div id="historyReferralTab" class="history-tab-pane {{ request('tab') === 'ref' ? '' : 'd-none' }}">
        @forelse($referralHistory as $ref)
            <div class="ludo-history-card" style="border-color: #f59e0b;">
                <div class="ludo-history-card-status" style="background: linear-gradient(90deg, #d97706, #f59e0b);">
                    {{ strtoupper($ref->status) }}
                </div>
                <div class="ludo-history-card-body">
                    <div class="ludo-history-info-left">
                        <div class="ludo-history-info-row">
                            <span class="label">Referral ID:</span>
                            <span class="text-white fw-bold">#REF-{{ str_pad($ref->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Referred Player:</span>
                            <span class="text-info">{{ $ref->referredUser->name ?? 'User #' . $ref->referred_user_id }}</span>
                        </div>
                        <div class="ludo-history-info-row">
                            <span class="label">Eligibility:</span>
                            <span class="badge {{ $ref->status === 'eligible' || $ref->status === 'rewarded' ? 'bg-success' : 'bg-warning text-dark' }} text-uppercase" style="font-size: 9.5px;">
                                {{ $ref->status }}
                            </span>
                        </div>
                        <div class="ludo-history-expiry-text text-success">
                            Reward: +{{ $ref->reward_points }} Virtual Points
                        </div>
                    </div>
                    <div class="ludo-history-badge">
                        <span class="text-success fw-bold fs-6">+{{ $ref->reward_points }}</span>
                        <span class="fs-6">&#129689;</span>
                    </div>
                </div>
                <div class="ludo-history-card-footer justify-content-between">
                    <span>&#128197; Registration: {{ $ref->created_at ? $ref->created_at->format('d M Y @ h:i A') : 'N/A' }}</span>
                    <span class="text-secondary small">{{ $ref->rewarded_at ? 'Rewarded ' . $ref->rewarded_at->diffForHumans() : 'Pending verification' }}</span>
                </div>
            </div>
        @empty
            <x-ludo.empty-state 
                title="No Referral History"
                message="You haven't referred any players yet. Share your code with friends to start earning bonus points!"
                actionText="Invite Friends"
                actionLink="{{ url('/refer') }}"
            />
        @endforelse

        @if($referralHistory->hasPages())
            <div class="d-flex justify-content-center my-3 ludo-pagination-wrapper">
                {{ $referralHistory->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
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
