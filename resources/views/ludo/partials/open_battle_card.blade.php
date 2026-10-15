@php
    $authId = auth()->id();
    $authUser = auth()->user();
    $authCoins = $authUser ? (int) $authUser->fresh()->coins : 0;
    $isCreator = $authId && $authId === $battle->creator_id;
    $isJoiner = $authId && $authId === $battle->joiner_id;
    $creatorName = $battle->creator ? ($battle->creator->name ?: 'Player_' . substr($battle->creator->phone ?? $battle->creator->id, -4)) : 'Player_' . $battle->creator_id;
    $joinerName = $battle->joiner ? ($battle->joiner->name ?: 'Player_' . substr($battle->joiner->phone ?? $battle->joiner->id, -4)) : null;
    $isRequested = $battle->status === 'requested';
    $hasSufficientCoins = $authCoins >= $battle->entry_fee;

    // Card State Classes
    if ($isCreator) {
        $stateClass = $isRequested 
            ? 'battle-card--own-requested battle-card-requested' 
            : 'battle-card--own-open battle-card-creator';
    } elseif ($isJoiner && $isRequested) {
        $stateClass = 'battle-card--own-requested battle-card-occupied';
    } elseif ($isRequested) {
        $stateClass = 'battle-card--opponent-open battle-card-occupied';
    } else {
        $stateClass = 'battle-card--opponent-open battle-card-open';
    }
@endphp

<div class="ludo-battle-card {{ $stateClass }}" 
     data-battle-id="{{ $battle->id }}" 
     data-battle-uid="{{ $battle->battle_uid }}" 
     data-entry-fee="{{ $battle->entry_fee }}" 
     data-prize="{{ $battle->prize }}" 
     data-creator-name="{{ $creatorName }}">

    @if($isCreator)
        {{-- Red Gaming Polygon Shards Overlay (Screenshots #1 & #2) --}}
        <svg class="ludo-red-shards" viewBox="0 0 400 90" preserveAspectRatio="none">
            <polygon points="0,0 45,0 15,35 0,25" fill="#ff1744" opacity="0.18" />
            <polygon points="0,55 35,90 0,90" fill="#ff1744" opacity="0.22" />
            <polygon points="10,80 25,60 45,85" fill="#ff5252" opacity="0.15" />
            <polygon points="340,90 380,60 400,90" fill="#ff1744" opacity="0.2" />
            <polygon points="360,0 400,0 400,40" fill="#ff1744" opacity="0.15" />
            <polygon points="330,75 355,85 345,90" fill="#ff5252" opacity="0.12" />
        </svg>
    @elseif(!$isRequested)
        {{-- Electric Blue Futuristic HUD Swoop Overlay (Screenshots #1 & #2) --}}
        <svg class="ludo-card-swoop" viewBox="0 0 180 90" preserveAspectRatio="none">
            <path d="M 50,0 C 85,18 95,60 175,90" fill="none" stroke="#0099ff" stroke-width="1.5" opacity="0.9"/>
            <path d="M 50,0 C 85,18 95,60 175,90 L 180,90 L 180,0 Z" fill="url(#blueSwoopGrad{{ $battle->id }})" opacity="0.12"/>
            <defs>
                <linearGradient id="blueSwoopGrad{{ $battle->id }}" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#00ffff"/>
                    <stop offset="100%" stop-color="#0066ff"/>
                </linearGradient>
            </defs>
        </svg>
    @endif

    {{-- Card Left Content --}}
    <div class="ludo-battle-left">
        <div class="ludo-battle-trophy-wrapper">
            <img src="{{ asset('assets/ludo/trophy.svg') }}" alt="Trophy" class="ludo-battle-trophy">
        </div>
        <div class="ludo-battle-details">
            <div class="ludo-battle-user-row">
                <div class="ludo-live-pill">
                    <span class="ludo-live-dot"></span>
                    <span>Live</span>
                </div>
                @if($isRequested)
                    <span class="ludo-battle-username text-truncate" title="{{ $creatorName }} vs {{ $joinerName }}">
                        {{ $creatorName }} <span class="text-warning">vs</span> {{ $joinerName }}
                    </span>
                @else
                    <span class="ludo-battle-username text-truncate" title="{{ $creatorName }}">
                        {{ $creatorName }}
                        @if($battle->creator)
                            <span class="text-info font-monospace fw-normal" style="font-size: 10px;">{{ $battle->creator->formatted_username }}</span>
                        @endif
                    </span>
                @endif
                <span class="ludo-battle-uid-tag d-none d-sm-inline">({{ $battle->battle_uid }})</span>
            </div>

            <div class="ludo-battle-pricing">
                <div class="ludo-price-col">
                    <span class="ludo-price-label">ENTRY FEE</span>
                    <span class="ludo-price-fee">🪙 {{ number_format($battle->entry_fee) }}</span>
                </div>
                <div class="ludo-price-divider"></div>
                <div class="ludo-price-col">
                    <span class="ludo-price-label">WINNING PRIZE</span>
                    <span class="ludo-price-win">🪙 {{ number_format($battle->prize) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Card Right Actions --}}
    <div class="ludo-battle-actions battle-card__actions">
        @if($isCreator)
            @if($isRequested)
                {{-- Screenshot #2: Creator sees START (Blue) and CANCEL (Red) circular buttons side-by-side --}}
                <div class="d-flex align-items-center gap-2">
                    <form action="{{ route('ludo.battles.start', $battle->id) }}" method="POST" class="d-inline m-0 p-0">
                        @csrf
                        <button type="submit" class="battle-action-btn battle-action--start ludo-btn-circle ludo-btn-start" title="Start Match">
                            <span>START</span>
                        </button>
                    </form>
                    <form action="{{ route('ludo.battles.cancel', $battle->id) }}" method="POST" class="d-inline m-0 p-0" onsubmit="return confirm('Cancel this match and refund entry points?');">
                        @csrf
                        <button type="submit" class="battle-action-btn battle-action--cancel ludo-btn-circle ludo-btn-cancel-round" title="Cancel Match">
                            <span>CANCEL</span>
                        </button>
                    </form>
                </div>
            @else
                {{-- Screenshot #1: Creator sees Red circular DELETE button when waiting for opponent --}}
                <div class="d-flex align-items-center gap-2">
                    <span class="ludo-waiting-spinner d-none d-sm-inline-block" title="Waiting for opponent">
                        <i class="fa-solid fa-spinner fa-spin text-danger" style="font-size: 13px; opacity: 0.7;"></i>
                    </span>
                    <form action="{{ route('ludo.battles.cancel', $battle->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Delete this battle and refund entry points?');">
                        @csrf
                        <button type="submit" class="battle-action-btn battle-action--delete ludo-btn-circle ludo-btn-delete" title="Delete Battle">
                            <span>DELETE</span>
                        </button>
                    </form>
                </div>
            @endif

        @elseif($isJoiner && $isRequested)
            {{-- Joiner waiting for creator to start --}}
            <div class="d-flex flex-column align-items-end gap-1">
                <a href="{{ route('ludo.battles.view', $battle->id) }}" class="btn btn-sm btn-info rounded-pill px-3 py-1 fw-bold" style="font-size: 11px;">
                    Arena <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
                <span class="text-warning small" style="font-size: 9.5px;">Waiting for creator...</span>
            </div>

        @elseif($isRequested)
            {{-- Third-party viewing a requested battle --}}
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 disabled" style="font-size: 10px; opacity: 0.65;">
                Starting...
            </button>

        @else
            {{-- Screenshots #1 & #2: Opponent can accept and play (Blue round PLAY button) --}}
            <button type="button" class="battle-action-btn battle-action--play ludo-btn-play" data-bs-toggle="modal" data-bs-target="#ludoPlayModal{{ $battle->id }}" title="Play Battle">
                <span>PLAY</span>
            </button>

            {{-- Play Confirmation Modal for this Battle --}}
            <div class="modal fade" id="ludoPlayModal{{ $battle->id }}" tabindex="-1" aria-labelledby="ludoPlayModalLabel{{ $battle->id }}" aria-hidden="true" data-battle-id="{{ $battle->id }}" data-entry-fee="{{ $battle->entry_fee }}">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content ludo-modal-dialog">
                        <div class="modal-header ludo-modal-header">
                            <h5 class="modal-title ludo-modal-title" id="ludoPlayModalLabel{{ $battle->id }}">
                                <span class="visually-hidden">Challenge {{ $creatorName }}</span>
                                <span class="text-warning me-1">⚔️</span><span class="text-white">Challenge </span><span class="text-warning fw-bold" style="color: #ffd700 !important;">{{ $creatorName }}</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white ludo-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body ludo-modal-body">
                            <p class="ludo-modal-intro">
                                You are joining battle <strong class="text-info fw-bold" style="color: #00d2ff !important;">{{ $battle->battle_uid }}</strong> created by <strong class="text-warning fw-bold" style="color: #ffd700 !important;">{{ $creatorName }} ({{ $battle->creator?->formatted_username }})</strong>.
                            </p>

                            {{-- Battle Details Card (Entry Points Required & Winning Reward) --}}
                            <div class="ludo-modal-inset-panel mb-3">
                                <div class="ludo-modal-row">
                                    <span class="ludo-modal-label">
                                        <i class="fa-solid fa-coins text-warning me-1"></i> Entry Points Required:
                                    </span>
                                    <span class="ludo-modal-val-fee">🪙 {{ number_format($battle->entry_fee) }}</span>
                                </div>
                                <div class="ludo-modal-row border-top border-secondary border-opacity-25 pt-2 mt-1">
                                    <span class="ludo-modal-label">
                                        <i class="fa-solid fa-trophy text-warning me-1"></i> Winning Reward:
                                    </span>
                                    <span class="ludo-modal-val-win">🪙 {{ number_format($battle->prize) }}</span>
                                </div>
                            </div>

                            @if(!$hasSufficientCoins)
                                {{-- Insufficient Points Warning Card (Dark Burgundy Premium) --}}
                                <div class="ludo-modal-insufficient-card mb-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="ludo-modal-warn-icon">
                                            <i class="fa-solid fa-triangle-exclamation text-danger fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="ludo-modal-warn-title">Insufficient Points Balance</div>
                                            <div class="ludo-modal-warn-text">
                                                You currently have <strong class="text-white user-coins-display">🪙 {{ number_format($authCoins) }}</strong>, but this battle requires <strong class="text-white">🪙 {{ number_format($battle->entry_fee) }}</strong> points. Add virtual points to continue.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Balance Status Panel (Current Balance & Shortfall) --}}
                                <div class="ludo-modal-balance-card mb-3">
                                    <div class="ludo-modal-row">
                                        <span class="ludo-modal-label">
                                            <i class="fa-solid fa-wallet text-info me-1"></i> Your Current Balance:
                                        </span>
                                        <span class="ludo-modal-val-cur">🪙 <span class="user-coins-display">{{ number_format($authCoins) }}</span></span>
                                    </div>
                                    <div class="ludo-modal-row border-top border-secondary border-opacity-25 pt-2 mt-1">
                                        <span class="ludo-modal-label" style="color: #fca5a5;">
                                            <i class="fa-solid fa-circle-exclamation text-danger me-1"></i> Points Shortfall:
                                        </span>
                                        <span class="ludo-modal-val-shortfall" style="color: #ef4444; font-weight: 800; font-size: 14px;">
                                            🪙 {{ number_format($battle->entry_fee - $authCoins) }} needed
                                        </span>
                                    </div>
                                </div>
                            @else
                                {{-- Sufficient Balance Verification Panel --}}
                                <div class="ludo-modal-balance-card mb-3">
                                    <div class="ludo-modal-row">
                                        <span class="ludo-modal-label">
                                            <i class="fa-solid fa-wallet text-info me-1"></i> Your Current Balance:
                                        </span>
                                        <span class="ludo-modal-val-cur">🪙 <span class="user-coins-display">{{ number_format($authCoins) }}</span></span>
                                    </div>
                                    <div class="ludo-modal-row border-top border-secondary border-opacity-25 pt-2 mt-1">
                                        <span class="ludo-modal-label">
                                            <i class="fa-solid fa-scale-balanced me-1" style="color: #00d2ff;"></i> Balance After Joining:
                                        </span>
                                        <span class="ludo-modal-val-after">🪙 {{ number_format($authCoins - $battle->entry_fee) }}</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Room Code Note --}}
                            <div class="ludo-modal-room-info">
                                <i class="fa-solid fa-circle-info text-info flex-shrink-0"></i>
                                <span>Room code will be generated upon match start. You will have 180 seconds to enter the room.</span>
                            </div>
                        </div>
                        <div class="modal-footer ludo-modal-footer">
                            @if(!$hasSufficientCoins)
                                <div class="ludo-modal-footer-insufficient">
                                    <button type="button" class="btn ludo-modal-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <div class="ludo-modal-actions-right">
                                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3 py-1 fw-bold text-white disabled d-none" disabled title="Insufficient points balance">
                                            <i class="fa-solid fa-lock me-1"></i> Insufficient Points
                                        </button>
                                        <a href="{{ url('/wallet') }}" class="btn ludo-modal-btn-add-points">
                                            <i class="fa-solid fa-coins text-warning me-1"></i> Add Points
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="ludo-modal-footer-sufficient">
                                    <button type="button" class="btn ludo-modal-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                                    <form action="{{ route('ludo.battles.join', $battle->id) }}" method="POST" class="d-inline ludo-join-battle-form" data-battle-id="{{ $battle->id }}">
                                        @csrf
                                        <button type="submit" class="btn ludo-modal-btn-accept ludo-btn-accept-play">
                                            <i class="fa-solid fa-play me-1"></i> Accept & Play
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>


