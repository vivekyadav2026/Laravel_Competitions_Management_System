@extends('layouts.player')

@section('title', 'Royal Ludo — Battles & Tournaments')

@section('content')
    {{-- Notice Banner --}}
    <x-ludo.announcement>
        <span class="fw-bold">⭐ 🙏 🙏 सभी यूजर ध्यान दें:</span> मैच शुरू होने के 15 मिनट के अंदर रूम कोड डालें। किसी भी विवाद की स्थिति में स्क्रीनशॉट अनिवार्य है।
    </x-ludo.announcement>

    {{-- Create a Battle Section (Screenshot #5 Top Card) --}}
    <div class="ludo-create-battle-card" id="create-battle">
        <div class="ludo-create-battle-header">
            <div class="ludo-create-battle-title">
                <i class="fa-solid fa-gamepad text-warning"></i>
                <span>Create a Battle!</span>
            </div>
            <button class="ludo-rules-btn" type="button" data-bs-toggle="modal" data-bs-target="#ludoRulesModal">
                <span>Rules</span> <i class="fa-solid fa-circle-info ms-1"></i>
            </button>
        </div>

        <form action="{{ route('ludo.battles.create') }}" method="POST">
            @csrf
            <div class="ludo-battle-input-group">
                <span class="text-warning fs-5">🪙</span>
                <input 
                    type="number" 
                    name="amount" 
                    class="ludo-battle-input @error('amount') is-invalid @enderror" 
                    placeholder="Enter points (min 50)" 
                    value="{{ old('amount', 50) }}"
                    min="50" 
                    max="50000"
                    step="50" 
                    required
                >
                <button type="submit" class="ludo-btn-set">
                    <i class="fa-solid fa-plus small"></i> Set
                </button>
            </div>
            @error('amount')
                <div class="text-danger small mt-2 fw-semibold">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}
                </div>
            @enderror
            <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                <span class="text-secondary small" style="font-size: 10.5px;">Virtual Points only • Free Fair Play</span>
                <span class="text-info small" style="font-size: 10.5px;">Est. Win Prize: 190%</span>
            </div>
        </form>
    </div>

    {{-- OPEN BATTLES Gold Banner --}}
    <x-ludo.gold-banner 
        title="OPEN BATTLES" 
        subtitle="Choose your battle and win big" 
    />

    {{-- Open Battles List --}}
    <div class="mb-4">
        @forelse($openBattles as $battle)
            @php
                $isCreator = auth()->check() && auth()->id() === $battle->creator_id;
                $creatorName = $battle->creator->name ?? 'Player_' . $battle->creator_id;
            @endphp
            <div class="ludo-battle-card">
                <div class="ludo-battle-left">
                    <img src="{{ asset('assets/ludo/trophy.svg') }}" alt="Trophy" class="ludo-battle-trophy">
                    <div class="ludo-battle-details">
                        <div class="ludo-battle-user-row">
                            <span class="badge bg-dark text-info border border-info border-opacity-50" style="font-size: 9.5px; border-radius: 12px;">
                                <span class="live-dot d-inline-block me-1" style="width: 5px; height: 5px; background: #00ff87; border-radius: 50%;"></span>Live
                            </span>
                            <span class="ludo-battle-username">{{ $creatorName }}</span>
                            <span class="text-secondary small" style="font-size: 9.5px;">({{ $battle->battle_uid }})</span>
                        </div>
                        <div class="ludo-battle-pricing">
                            <div class="ludo-price-col">
                                <span class="ludo-price-label">ENTRY POINTS</span>
                                <span class="ludo-price-fee">🪙 {{ number_format($battle->entry_fee) }}</span>
                            </div>
                            <div class="ludo-price-col">
                                <span class="ludo-price-label">WINNING PRIZE</span>
                                <span class="ludo-price-win">🪙 {{ number_format($battle->prize) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($isCreator)
                    {{-- Creator can cancel their own open challenge --}}
                    <form action="{{ route('ludo.battles.cancel', $battle->id) }}" method="POST" onsubmit="return confirm('Cancel this battle and refund {{ $battle->entry_fee }} points?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" style="font-size: 11px; font-weight: 700;">
                            CANCEL
                        </button>
                    </form>
                @else
                    {{-- Opponent can accept and play --}}
                    <button type="button" class="ludo-btn-play" data-bs-toggle="modal" data-bs-target="#ludoPlayModal{{ $battle->id }}">
                        PLAY
                    </button>

                    {{-- Play Confirmation Modal for this Battle --}}
                    <div class="modal fade" id="ludoPlayModal{{ $battle->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered px-3">
                            <div class="modal-content ludo-modal-dialog">
                                <div class="modal-header border-secondary border-opacity-25 pb-2">
                                    <h5 class="modal-title fw-bold fs-6">
                                        <span class="text-warning">⚔️</span> Challenge {{ $creatorName }}
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body text-secondary small py-3">
                                    <p class="text-light mb-2">
                                        You are joining battle <strong class="text-info">{{ $battle->battle_uid }}</strong> created by <strong class="text-warning">{{ $creatorName }}</strong>.
                                    </p>
                                    <div class="p-2 rounded-3 mb-2" style="background: rgba(14, 42, 92, 0.7); border: 1px solid #1d4ed8;">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Entry Points Deducted:</span>
                                            <span class="fw-bold text-white">🪙 {{ number_format($battle->entry_fee) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Winning Reward:</span>
                                            <span class="fw-bold text-success">🪙 {{ number_format($battle->prize) }}</span>
                                        </div>
                                    </div>
                                    <small class="text-secondary d-block">Room code will be generated upon match start.</small>
                                </div>
                                <div class="modal-footer border-secondary border-opacity-25 pt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                    <form action="{{ route('ludo.battles.join', $battle->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-info rounded-pill px-4 fw-bold text-dark">
                                            Accept & Play
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <x-ludo.empty-state 
                title="No Open Battles Right Now"
                message="Be the first to challenge other players! Set an entry amount above to create a battle."
            />
        @endforelse

        {{-- Open Battles Pagination Links --}}
        @if($openBattles->hasPages())
            <div class="d-flex justify-content-center my-3 ludo-pagination-wrapper">
                {{ $openBattles->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- RUNNING BATTLES Gold Banner --}}
    <x-ludo.gold-banner 
        title="RUNNING BATTLES" 
        subtitle="Join running battles and compete to win" 
    />

    {{-- Running Battles List --}}
    <div class="mb-4">
        @forelse($runningBattles as $running)
            @php
                $p1 = $running->creator->name ?? 'Player_1';
                $p2 = $running->joiner->name ?? 'Player_2';
            @endphp
            <div class="ludo-running-card">
                <div class="ludo-running-header">
                    <span class="ludo-running-label">Playing For</span>
                    <span class="ludo-running-players-badge">
                        {{ $p1 }} <span class="text-warning">&</span> {{ $p2 }}
                    </span>
                </div>

                <div class="ludo-running-matchup">
                    <div class="ludo-matchup-box">
                        <div class="ludo-price-label">ENTRY FEE</div>
                        <div class="ludo-price-fee text-info">🪙 {{ number_format($running->entry_fee) }}</div>
                    </div>

                    <img src="{{ asset('assets/ludo/vs-badge.svg') }}" alt="VS" class="ludo-matchup-vs">

                    <div class="ludo-matchup-box">
                        <div class="ludo-price-label">WINNING PRIZE</div>
                        <div class="ludo-price-win">🪙 {{ number_format($running->prize) }}</div>
                    </div>
                </div>

                @if($running->room_code)
                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                        <span class="text-secondary small" style="font-size: 10px;">Ref: {{ $running->battle_uid }}</span>
                        <span class="badge bg-dark text-warning border border-warning border-opacity-50" style="font-size: 10px;">
                            Room: {{ $running->room_code }}
                        </span>
                    </div>
                @endif
            </div>
        @empty
            <x-ludo.empty-state 
                title="No Matches Currently In Progress"
                message="Matches will appear here as soon as two players begin a contest."
            />
        @endforelse

        {{-- Running Battles Pagination Links --}}
        @if($runningBattles->hasPages())
            <div class="d-flex justify-content-center my-3 ludo-pagination-wrapper">
                {{ $runningBattles->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    {{-- Rules Modal --}}
    <div class="modal fade" id="ludoRulesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered px-3">
            <div class="modal-content ludo-modal-dialog">
                <div class="modal-header border-secondary border-opacity-25 pb-2">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="fa-solid fa-scroll text-warning me-2"></i> Ludo Tournament Rules
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-secondary small py-3">
                    <ol class="ps-3 mb-0 text-light d-flex flex-column gap-2">
                        <li>The match creator must share the Ludo King Room Code within 5 minutes.</li>
                        <li>Opponent must join within 5 minutes of room code being provided.</li>
                        <li>Take a screenshot of the winning screen for verification.</li>
                        <li>Cancellation or fake results will lead to immediate account suspension.</li>
                        <li>Platform fee is calculated in the winning prize. Battles use non-cash virtual points only.</li>
                    </ol>
                </div>
                <div class="modal-footer border-secondary border-opacity-25 pt-2">
                    <button type="button" class="btn btn-sm btn-info rounded-pill px-4 text-dark fw-bold" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
@endsection
