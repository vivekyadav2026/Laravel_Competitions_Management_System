@php
    $isCreator = auth()->check() && auth()->id() === $battle->creator_id;
    $creatorName = $battle->creator->name ?? 'Player_' . $battle->creator_id;
@endphp
<div class="ludo-battle-card" data-battle-id="{{ $battle->id }}">
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
