@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Match Arena (' . $battle->battle_uid . ')')

@section('content')
    {{-- Hindi Regulatory / Fair Play Announcement Notice Banner (Screenshot #4) --}}
    <x-ludo.announcement>
        {!! $announcement ?? '🙏 🙏 सभी प्लेयरों से निवेदन है कि रिजल्ट सही डालें यदि बिना गोटी ओपन हुए कोई बंदा लेफ्ट हो जाता है या ऑटो एग्जिट हो जाता है तो टेबल कैंसिल ही मानी जाएगी अत्यंत नियोजन है कि उसमें स्क्रीनशॉट अपलोड ना करें और टेबल कैंसिल होगी तो कैंसिल रिजल्ट डालें उसमें 📢' !!}
    </x-ludo.announcement>

    @php
        $p1 = $battle->creator;
        $p2 = $battle->joiner;
        $p1Name = $p1->name ?? 'Player_' . $battle->creator_id;
        $p2Name = $p2 ? ($p2->name ?? 'Player_' . $battle->joiner_id) : 'Waiting...';
        $isP1 = $user && $user->id === $battle->creator_id;
        $isP2 = $user && $user->id === $battle->joiner_id;
        $isParticipant = $isP1 || $isP2;
    @endphp

    {{-- Match Arena Versus Card (Screenshot #4) --}}
    <div class="ludo-arena-card mb-3">
        <div class="ludo-arena-header d-flex justify-content-between align-items-center mb-3">
            <span class="text-secondary small fw-semibold">
                Match ID: <span class="text-info font-monospace">{{ $battle->battle_uid }}</span>
            </span>
            @if($battle->status === 'running')
                <span class="badge bg-danger text-white border border-danger px-2 py-1" style="font-size: 10px;">
                    <span class="live-dot d-inline-block me-1" style="width: 6px; height: 6px; background: #fff; border-radius: 50%;"></span>LIVE BATTLE
                </span>
            @elseif($battle->status === 'requested')
                <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 10px;">
                    <span class="spinner-grow spinner-grow-sm me-1" style="width: 6px; height: 6px;"></span>WAITING TO START
                </span>
            @elseif($battle->status === 'completed')
                <span class="badge bg-success text-white px-2 py-1" style="font-size: 10px;">
                    <i class="fa-solid fa-check me-1"></i>COMPLETED
                </span>
            @elseif($battle->status === 'cancelled')
                <span class="badge bg-secondary text-white px-2 py-1" style="font-size: 10px;">
                    <i class="fa-solid fa-ban me-1"></i>CANCELLED
                </span>
            @else
                <span class="badge bg-info text-dark px-2 py-1" style="font-size: 10px;">
                    OPEN CHALLENGE
                </span>
            @endif
        </div>

        {{-- Dual Player Matchup Row with VS Orb --}}
        <div class="ludo-arena-versus-row">
            {{-- Player 1 (Creator) --}}
            <div class="ludo-arena-player-col text-center">
                <div class="ludo-arena-avatar-wrapper {{ $isP1 ? 'is-self' : '' }}">
                    <div class="ludo-arena-avatar">
                        {{ strtoupper(mb_substr($p1Name, 0, 1)) }}
                    </div>
                    @if($isP1)
                        <span class="ludo-arena-you-badge">YOU</span>
                    @endif
                </div>
                <div class="ludo-arena-player-name text-truncate" title="{{ $p1Name }}">{{ $p1Name }}</div>
                <div class="text-secondary small" style="font-size: 10px;">Creator</div>
            </div>

            {{-- Center VS Orb and Prize Details --}}
            <div class="ludo-arena-center-col text-center">
                <div class="ludo-arena-vs-badge">
                    <span>VS</span>
                </div>
                <div class="mt-2">
                    <div class="text-secondary small" style="font-size: 9.5px; font-weight: 700;">ENTRY FEE</div>
                    <div class="text-white fw-bold fs-6">🪙 {{ number_format($battle->entry_fee) }}</div>
                </div>
                <div class="mt-1">
                    <div class="text-secondary small" style="font-size: 9.5px; font-weight: 700;">WIN PRIZE</div>
                    <div class="text-success fw-bold fs-5">🪙 {{ number_format($battle->prize) }}</div>
                </div>
            </div>

            {{-- Player 2 (Joiner) --}}
            <div class="ludo-arena-player-col text-center">
                <div class="ludo-arena-avatar-wrapper {{ $isP2 ? 'is-self' : '' }}">
                    <div class="ludo-arena-avatar {{ !$p2 ? 'is-empty' : '' }}">
                        @if($p2)
                            {{ strtoupper(mb_substr($p2Name, 0, 1)) }}
                        @else
                            <i class="fa-solid fa-user-plus text-secondary" style="font-size: 18px;"></i>
                        @endif
                    </div>
                    @if($isP2)
                        <span class="ludo-arena-you-badge">YOU</span>
                    @endif
                </div>
                <div class="ludo-arena-player-name text-truncate" title="{{ $p2Name }}">{{ $p2Name }}</div>
                <div class="text-secondary small" style="font-size: 10px;">Opponent</div>
            </div>
        </div>
    </div>

    {{-- Administrative Review & Frozen Settlement Banner --}}
    @if(in_array($battle->review_status, ['under_review', 'disputed']) || $battle->refund_status === 'pending')
        <div class="ludo-arena-card mb-3 p-3" style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.4);">
            <div class="d-flex align-items-center gap-3">
                <div class="text-warning fs-3">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h6 class="text-warning fw-bold mb-1">
                        <i class="fa-solid fa-lock me-1"></i> Under Administrative Review
                    </h6>
                    <p class="small text-light mb-0" style="font-size: 11.5px;">
                        This match is currently held for administrator audit. Payouts and refunds are frozen until an admin verifies both players' claims and evidence.
                        @if($battle->refund_status === 'pending')
                            <span class="badge bg-warning text-dark ms-1">Refund Pending Verification</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- If Status is 'requested' and User is Creator: START Button --}}
    @if($battle->status === 'requested' && $isP1)
        <div class="ludo-action-alert-card mb-3 p-3 text-center">
            <h6 class="text-warning fw-bold mb-1">
                <i class="fa-solid fa-bell me-1"></i> Opponent {{ $p2Name }} Has Joined!
            </h6>
            <p class="small text-secondary mb-3">
                Click START to lock the match and enter the 180s room code window.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <form action="{{ route('ludo.battles.start', $battle->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-lg">
                        <i class="fa-solid fa-play me-1"></i> START BATTLE
                    </button>
                </form>
                <form action="{{ route('ludo.battles.cancel', $battle->id) }}" method="POST" onsubmit="return confirm('Cancel this match and refund points?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger px-4 py-2 rounded-pill fw-bold">
                        CANCEL
                    </button>
                </form>
            </div>
        </div>
    @elseif($battle->status === 'requested' && $isP2)
        <div class="ludo-action-alert-card mb-3 p-3 text-center">
            <h6 class="text-info fw-bold mb-1">
                <span class="spinner-grow spinner-grow-sm me-1 text-warning" role="status"></span> Waiting for {{ $p1Name }} to Start
            </h6>
            <p class="small text-secondary mb-0">
                You have joined! Match will begin as soon as the creator presses START.
            </p>
        </div>
    @endif

    {{-- Ludo King Room Code Card (Screenshot #4) --}}
    @if(in_array($battle->status, ['running', 'requested']))
        <div class="ludo-arena-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-white mb-0 fs-6">
                    <i class="fa-solid fa-key text-warning me-1"></i>
                    {{ $battle->room_code ? 'Ludo King Room Code' : 'Set Ludo King Room Code' }}
                </h5>

                {{-- Live Countdown Timer (180 Seconds) --}}
                <div class="ludo-timer-badge" id="roomCodeTimerBadge">
                    <i class="fa-regular fa-clock me-1 text-warning"></i>
                    <span id="countdownDisplay" class="fw-bold">{{ $remainingSeconds ?? 180 }}s</span>
                </div>
            </div>

            @if($battle->room_code)
                {{-- Room Code is Already Set --}}
                <div class="ludo-room-code-box text-center py-3 mb-3">
                    <div class="text-secondary small mb-1" style="font-size: 11px;">ROOM CODE FOR LUDO KING</div>
                    <div class="ludo-room-code-digits" id="roomCodeText">{{ $battle->room_code }}</div>
                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-4 mt-2 fw-bold" id="btnCopyRoomCode">
                        <i class="fa-solid fa-copy me-1"></i> <span id="copyBtnText">Copy Code</span>
                    </button>
                </div>

                {{-- Launch Ludo King App Deep-link Button --}}
                <div class="d-grid gap-2 mb-3">
                    <a href="ludoking://" class="btn btn-success py-2 rounded-3 fw-bold fs-6 shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="fa-solid fa-dice text-warning fs-5"></i>
                        <span>Open Ludo King App</span>
                    </a>
                </div>

                <div class="p-2 rounded-3 mb-2" style="background: rgba(14, 42, 92, 0.4); border: 1px dashed rgba(0, 210, 255, 0.3);">
                    <small class="text-secondary d-block" style="font-size: 11px;">
                        📌 <strong class="text-light">How to Play:</strong> Open Ludo King &rarr; Tap 'Play With Friends' &rarr; Select 'Join' &rarr; Paste Code <strong>{{ $battle->room_code }}</strong> &rarr; Play fair!
                    </small>
                </div>
            @else
                {{-- Room Code Not Yet Set: Creator or Joiner can upload it --}}
                @if($isParticipant)
                    <form action="{{ route('ludo.battles.room-code', $battle->id) }}" method="POST" id="setRoomCodeForm" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label text-light fw-bold small">Enter Room Code from Ludo King App</label>
                            <div class="input-group">
                                <input 
                                    type="text" 
                                    name="room_code" 
                                    id="inputRoomCode" 
                                    class="form-control bg-dark text-light border-info font-monospace fw-bold fs-5 text-center" 
                                    placeholder="e.g. 05839201" 
                                    required 
                                    minlength="4" 
                                    maxlength="20"
                                    autocomplete="off"
                                >
                                <button type="submit" class="btn btn-info fw-bold px-4 text-dark" id="btnSubmitRoomCode">
                                    <i class="fa-solid fa-check me-1"></i> Set
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="p-2 rounded-3 mb-2" style="background: rgba(14, 42, 92, 0.4); border: 1px solid rgba(0, 210, 255, 0.2);">
                        <small class="text-secondary d-block" style="font-size: 11px;">
                            💡 Open <strong>Ludo King</strong> &rarr; Select <strong>Play With Friends</strong> &rarr; Tap <strong>Create Room</strong> &rarr; Enter that Room Code above and press <strong>Set</strong>.
                        </small>
                    </div>
                @else
                    <div class="text-center py-3 text-secondary small">
                        Waiting for match participants to upload room code...
                    </div>
                @endif
            @endif

            {{-- Match Status & Cancel Button (Screenshot #4) --}}
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25 mt-2">
                <span class="text-secondary small" style="font-size: 11.5px;">
                    Match Status: <strong class="text-warning text-capitalize">{{ $battle->status }}</strong>
                </span>

                @if($isParticipant && in_array($battle->status, ['open', 'requested']) && !in_array($battle->review_status, ['under_review', 'disputed']) && $battle->refund_status !== 'pending')
                    {{-- Cancel Button opens Screenshot #5 Modal (Only allowed before match starts) --}}
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-bold" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#cancelReasonModal">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Cancel Match
                    </button>
                @endif
            </div>
        </div>
    @endif

    {{-- Submit Result Section when match is running (Screenshot #4 Result flow) --}}
    @if($battle->status === 'running' && $isParticipant)
        <div class="ludo-arena-card mb-4">
            <h5 class="fw-bold text-white mb-2 fs-6">
                <i class="fa-solid fa-trophy text-warning me-1"></i> Submit Match Result
            </h5>
            <p class="small text-secondary mb-3" style="font-size: 11.5px;">
                Once the Ludo King game concludes, upload your winning screenshot here. You have a 15-minute window.
            </p>

            <form action="{{ route('ludo.battles.result', $battle->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-light fw-bold small">Your Result Claim <span class="text-danger">*</span></label>
                    <select name="result" class="form-select bg-dark text-light border-secondary border-opacity-50 small" required>
                        <option value="won">🏆 I Won the Match</option>
                        <option value="lost">❌ I Lost the Match</option>
                        <option value="cancelled">⚠️ Match Cancelled / Not Played</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light fw-bold small">Winning Screenshot (Optional if opponent confirms)</label>
                    <input type="file" name="evidence" class="form-control bg-dark text-light border-secondary border-opacity-50 small" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-secondary d-block mt-1" style="font-size: 10.5px;">
                        Upload full end-game screen showing final points to expedite coin payout.
                    </small>
                </div>

                <button type="submit" class="btn btn-success w-100 py-2 rounded-3 fw-bold">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Submit Result Claim
                </button>
            </form>
        </div>
    @endif

    {{-- Completed / Cancelled Status Card --}}
    @if(in_array($battle->status, ['completed', 'cancelled']))
        <div class="ludo-arena-card mb-4 text-center py-4">
            @if($battle->status === 'completed')
                <div class="fs-1 text-warning mb-2">🏆</div>
                <h4 class="fw-bold text-white mb-1">Battle Finished!</h4>
                <p class="text-success fw-bold mb-2">
                    Winner: {{ $battle->winner->name ?? 'Player #' . $battle->winner_id }} (Prize: 🪙 {{ number_format($battle->prize) }})
                </p>
            @else
                <div class="fs-1 text-danger mb-2">🚫</div>
                <h4 class="fw-bold text-white mb-1">Battle Cancelled</h4>
                <p class="text-secondary small mb-2">
                    Reason: <span class="text-warning">{{ $battle->cancellation_reason ?? 'Cancelled by players' }}</span>
                </p>
                <div class="text-success small fw-semibold">
                    🪙 {{ number_format($battle->entry_fee) }} points refunded to player wallets.
                </div>
            @endif
            <div class="mt-3">
                <a href="{{ route('ludo.battles') }}" class="btn btn-outline-info rounded-pill px-4 py-1 small fw-bold">
                    &larr; Back to Battles Lobby
                </a>
            </div>
        </div>
    @endif

    {{-- Cancellation Reason Modal (Screenshot #5 Reference) --}}
    <div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered px-3">
            <div class="modal-content ludo-modal-dialog">
                <div class="modal-header border-secondary border-opacity-25 pb-2">
                    <h5 class="modal-title fw-bold fs-6 text-white" id="cancelReasonModalLabel">
                        Cancel Match Reason
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('ludo.battles.cancel-with-reason', $battle->id) }}" method="POST" id="cancelMatchReasonForm">
                    @csrf
                    <div class="modal-body text-secondary small py-3">
                        <div class="ludo-cancel-reasons-list d-flex flex-column gap-2 mb-3">
                            {{-- Reason 1: No Room code --}}
                            <label class="ludo-cancel-reason-option">
                                <input type="radio" name="reason" value="No Room code" checked class="form-check-input me-2">
                                <span>No Room code</span>
                            </label>

                            {{-- Reason 2: Not joined --}}
                            <label class="ludo-cancel-reason-option">
                                <input type="radio" name="reason" value="Not joined" class="form-check-input me-2">
                                <span>Not joined</span>
                            </label>

                            {{-- Reason 3: Not Playing --}}
                            <label class="ludo-cancel-reason-option">
                                <input type="radio" name="reason" value="Not Playing" class="form-check-input me-2">
                                <span>Not Playing</span>
                            </label>

                            {{-- Reason 4: Dont Want to Play --}}
                            <label class="ludo-cancel-reason-option">
                                <input type="radio" name="reason" value="Dont Want to Play" class="form-check-input me-2">
                                <span>Dont Want to Play</span>
                            </label>

                            {{-- Reason 5: Opponent Abusing --}}
                            <label class="ludo-cancel-reason-option">
                                <input type="radio" name="reason" value="Opponent Abusing" class="form-check-input me-2">
                                <span>Opponent Abusing</span>
                            </label>

                            {{-- Reason 6: Game not Started --}}
                            <label class="ludo-cancel-reason-option">
                                <input type="radio" name="reason" value="Game not Started" class="form-check-input me-2">
                                <span>Game not Started</span>
                            </label>

                            {{-- Reason 7: Others --}}
                            <label class="ludo-cancel-reason-option">
                                <input type="radio" name="reason" value="Others" class="form-check-input me-2">
                                <span>Others</span>
                            </label>
                        </div>

                        <div class="p-2 rounded-2" style="background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(239, 68, 68, 0.3);">
                            <small class="text-danger d-block" style="font-size: 11px;">
                                ⚠️ Notice: Cancellation is strictly permitted prior to match start. Both participants' entry points (🪙 {{ number_format($battle->entry_fee) }}) are returned to wallet. Running matches require dispute verification.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary border-opacity-25 pt-2 d-flex justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
                            Close
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Live Countdown Timer (180 Seconds)
            let remainingSeconds = {{ (int) ($remainingSeconds ?? 180) }};
            const displayEl = document.getElementById('countdownDisplay');

            if (displayEl && remainingSeconds > 0) {
                const interval = setInterval(function() {
                    remainingSeconds--;
                    if (remainingSeconds <= 0) {
                        clearInterval(interval);
                        displayEl.textContent = '0s (Expired)';
                        displayEl.classList.add('text-danger');
                    } else {
                        const mins = Math.floor(remainingSeconds / 60);
                        const secs = remainingSeconds % 60;
                        displayEl.textContent = `${remainingSeconds}s (${mins}:${secs < 10 ? '0' : ''}${secs})`;
                    }
                }, 1000);
            }

            // Copy Room Code Button
            const copyBtn = document.getElementById('btnCopyRoomCode');
            const roomCodeText = document.getElementById('roomCodeText');
            const copyBtnText = document.getElementById('copyBtnText');

            if (copyBtn && roomCodeText) {
                copyBtn.addEventListener('click', function() {
                    const code = roomCodeText.textContent.trim();
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(code).then(function() {
                            copyBtnText.textContent = 'Copied!';
                            copyBtn.classList.remove('btn-outline-info');
                            copyBtn.classList.add('btn-success');
                            setTimeout(() => {
                                copyBtnText.textContent = 'Copy Code';
                                copyBtn.classList.remove('btn-success');
                                copyBtn.classList.add('btn-outline-info');
                            }, 2500);
                        });
                    } else {
                        // Fallback
                        const tempInput = document.createElement('input');
                        tempInput.value = code;
                        document.body.appendChild(tempInput);
                        tempInput.select();
                        document.execCommand('copy');
                        document.body.removeChild(tempInput);
            // Real-Time Match Arena State Synchronization
            const battleId = {{ (int) $battle->id }};
            const currentStatus = "{{ $battle->status }}";
            const hasRoomCode = {{ !empty($battle->room_code) ? 'true' : 'false' }};

            if (currentStatus === 'requested' || currentStatus === 'running') {
                let isPolling = false;
                const pollInterval = setInterval(async function() {
                    if (document.hidden || isPolling) return;
                    isPolling = true;

                    try {
                        const res = await fetch("{{ route('ludo.battles.syncState') }}?battle_id=" + battleId, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (res.ok) {
                            const data = await res.json();
                            if (data.battle) {
                                // 1. Match status transitioned (e.g. requested -> running, or running -> completed / cancelled)
                                if (data.battle.status !== currentStatus) {
                                    clearInterval(pollInterval);
                                    window.location.reload();
                                    return;
                                }

                                // 2. Room code set by other player while this player was waiting
                                if (!hasRoomCode && data.battle.room_code) {
                                    clearInterval(pollInterval);
                                    window.location.reload();
                                    return;
                                }
                            }
                        }
                    } catch (err) {
                        console.debug("Arena sync poll:", err);
                    } finally {
                        isPolling = false;
                    }
                }, 3500);
            }
        });
    </script>
    @endpush
@endsection
