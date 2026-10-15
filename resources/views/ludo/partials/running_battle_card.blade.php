@php
    $p1 = $running->creator->name ?? 'Player_1';
    $p2 = $running->joiner->name ?? 'Player_2';
    $isParticipant = auth()->check() && (auth()->id() === $running->creator_id || auth()->id() === $running->joiner_id);
    $isAdmin = auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->is_admin);
@endphp

<div class="ludo-running-card {{ $isParticipant ? 'running-card-mine' : '' }}" data-battle-id="{{ $running->id }}" data-battle-uid="{{ $running->battle_uid }}">
    {{-- Top Row: "Playing For" & Player Names --}}
    <div class="ludo-running-header">
        <div class="d-flex align-items-center gap-2">
            <span class="ludo-running-pulse-dot"></span>
            <span class="ludo-running-label">Playing For</span>
        </div>
        <div class="ludo-running-players-badge text-truncate" title="{{ $p1 }} vs {{ $p2 }}">
            <span class="text-truncate" style="max-width: 110px;">{{ $p1 }}</span>
            <span class="ludo-vs-text">VS</span>
            <span class="text-truncate" style="max-width: 110px;">{{ $p2 }}</span>
        </div>
    </div>

    {{-- Middle Row: Entry Fee | VS | Winning Prize --}}
    <div class="ludo-running-matchup">
        <div class="ludo-matchup-box">
            <div class="ludo-price-label text-warning">ENTRY FEE</div>
            <div class="ludo-price-fee text-warning fw-bold">🪙 {{ number_format($running->entry_fee) }}</div>
        </div>

        <div class="ludo-matchup-vs-container">
            <img src="{{ asset('assets/ludo/vs-badge.svg') }}" alt="VS" class="ludo-matchup-vs">
        </div>

        <div class="ludo-matchup-box">
            <div class="ludo-price-label text-warning">WINNING PRIZE</div>
            <div class="ludo-price-win text-success fw-bold">🪙 {{ number_format($running->prize) }}</div>
        </div>
    </div>

    {{-- Bottom Row: Battle Reference, Match Time, Room Status & Action Button --}}
    <div class="ludo-running-footer">
        <div class="ludo-running-meta">
            <span class="ludo-running-ref">
                Ref: <strong class="text-info">{{ $running->battle_uid }}</strong>
            </span>
            @if($running->started_at)
                <span class="ludo-running-elapsed">
                    <i class="fa-regular fa-clock me-1"></i>{{ $running->started_at->diffForHumans(null, true, true) }}
                </span>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            @if($isParticipant || $isAdmin)
                @if($running->room_code)
                    <span class="badge bg-dark text-warning border border-warning border-opacity-50 px-2 py-1" style="font-size: 10px;" title="Ludo King Room Code">
                        <i class="fa-solid fa-key me-1"></i>Room: {{ $running->room_code }}
                    </span>
                @else
                    <span class="badge bg-dark text-secondary border border-secondary border-opacity-50 px-2 py-1" style="font-size: 9.5px;">
                        <span class="spinner-grow spinner-grow-sm me-1 text-warning" style="width: 5px; height: 5px;"></span>Room Pending
                    </span>
                @endif
                <a href="{{ route('ludo.battles.view', $running->id) }}" class="ludo-running-arena-btn" title="Open Match Arena">
                    <i class="fa-solid fa-gamepad me-1"></i> Match Arena
                </a>
            @else
                @if($running->room_code)
                    <span class="badge bg-dark text-info border border-info border-opacity-50 px-2 py-1" style="font-size: 9.5px;">
                        <span class="live-dot d-inline-block me-1" style="width: 5px; height: 5px; background: #00ff87; border-radius: 50%;"></span>In Play
                    </span>
                @else
                    <span class="badge bg-dark text-secondary border border-secondary border-opacity-50 px-2 py-1" style="font-size: 9.5px;">
                        <span class="spinner-grow spinner-grow-sm me-1 text-info" style="width: 5px; height: 5px;"></span>Starting...
                    </span>
                @endif
            @endif
        </div>
    </div>
</div>
