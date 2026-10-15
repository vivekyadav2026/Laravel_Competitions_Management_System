@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Match Arena (' . $battle->battle_uid . ')')

@section('content')
    {{-- Floating Live Toast Container --}}
    <div id="arenaToastContainer" style="position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 99999; max-width: 90%; min-width: 280px; pointer-events: none;"></div>

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
            <div id="arenaHeaderStatusBadgeContainer">
                @if($battle->status === 'running')
                    <span class="badge bg-danger text-white border border-danger px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;">
                        <span class="live-dot d-inline-block me-1" style="width: 6px; height: 6px; background: #fff; border-radius: 50%;"></span>LIVE BATTLE
                    </span>
                @elseif($battle->status === 'requested')
                    <span class="badge bg-warning text-dark px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;">
                        <span class="spinner-grow spinner-grow-sm me-1" style="width: 6px; height: 6px;"></span>WAITING TO START
                    </span>
                @elseif($battle->status === 'completed')
                    <span class="badge bg-success text-white px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;">
                        <i class="fa-solid fa-check me-1"></i>COMPLETED
                    </span>
                @elseif($battle->status === 'cancelled')
                    <span class="badge bg-secondary text-white px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;">
                        <i class="fa-solid fa-ban me-1"></i>CANCELLED
                    </span>
                @else
                    <span class="badge bg-info text-dark px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;">
                        OPEN CHALLENGE
                    </span>
                @endif
            </div>
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
                <div class="text-info small fw-bold font-monospace" style="font-size: 10px;">{{ $p1->formatted_username ?? ('@' . Str::slug($p1Name)) }}</div>
                <div class="text-secondary small" style="font-size: 9.5px;">Creator</div>
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
                @if($p2)
                    <div class="text-warning small fw-bold font-monospace" style="font-size: 10px;">{{ $p2->formatted_username ?? ('@' . Str::slug($p2Name)) }}</div>
                @endif
                <div class="text-secondary small" style="font-size: 9.5px;">Opponent</div>
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
        <div class="ludo-action-alert-card mb-3 p-3 text-center" id="waitingCreatorCard">
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
        <div class="ludo-arena-card mb-3" id="roomCodeCard">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-white mb-0 fs-6">
                    <i class="fa-solid fa-key text-warning me-1"></i>
                    <span id="roomCodeCardTitle">{{ $battle->room_code ? 'Ludo King Room Code' : 'Set Ludo King Room Code' }}</span>
                </h5>

                {{-- Live Countdown Timer (180 Seconds) --}}
                <div class="ludo-timer-badge" id="roomCodeTimerBadge">
                    <i class="fa-regular fa-clock me-1 text-warning"></i>
                    <span id="countdownDisplay" class="fw-bold">{{ $remainingSeconds ?? 180 }}s</span>
                </div>
            </div>

            <div id="roomCodeDynamicContainer">
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
            </div>

            {{-- Match Status & Cancel Button (Screenshot #4) --}}
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-25 mt-2">
                <span class="text-secondary small" style="font-size: 11.5px;">
                    Match Status: <strong class="text-warning text-capitalize" id="arenaMatchStatusText">{{ $battle->status }}</strong>
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

    {{-- Dynamic Match Completion / Cancellation Banner --}}
    <div id="matchCompletedBannerContainer">
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
    </div>

    {{-- Match Result & Dispute Section --}}
    @php
        $myClaim = $isP1 ? $battle->creator_claimed_result : $battle->joiner_claimed_result;
        $opponentClaim = $isP1 ? $battle->joiner_claimed_result : $battle->creator_claimed_result;
    @endphp

    <div id="matchResultSectionContainer" class="{{ in_array($battle->status, ['completed', 'cancelled']) ? 'd-none' : '' }}">
        @if($battle->status === 'running' && $isParticipant)
            @if($myClaim && $myClaim !== 'pending')
                {{-- Player has already submitted claim --}}
                <div class="ludo-arena-card mb-4 text-center p-3" id="myClaimSubmittedCard">
                    <h5 class="fw-bold text-white mb-2 fs-6">
                        <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> Match Result Status
                    </h5>
                    <div class="p-3 rounded-3 mb-2" style="background: rgba(14, 42, 92, 0.4); border: 1px solid rgba(0, 210, 255, 0.3);">
                        <div class="small text-secondary mb-1">Your Claimed Result:</div>
                        <div class="fw-bold fs-5 text-uppercase {{ $myClaim === 'won' ? 'text-success' : ($myClaim === 'lost' ? 'text-danger' : 'text-warning') }}" id="myClaimDisplay">
                            {{ $myClaim === 'won' ? '🏆 Victory Claimed (Won)' : ($myClaim === 'lost' ? '❌ Loss Claimed (Lost)' : '⚠️ Cancellation Claimed') }}
                        </div>
                        <div class="small text-light mt-1" id="myClaimNotice">
                            {{ $myClaim === 'won' ? 'Your victory claim has been submitted. Awaiting verification.' : 'Your result claim has been recorded.' }}
                        </div>
                        <div class="small text-secondary mt-2" id="opponentClaimStatusText">
                            @if($opponentClaim && $opponentClaim !== 'pending')
                                @if($opponentClaim === 'won')
                                    <span class="text-warning fw-bold"><i class="fa-solid fa-bullhorn me-1"></i> Your opponent has submitted a Victory Claim. Awaiting verification.</span>
                                @else
                                    Opponent Claim: <strong class="text-light text-uppercase">{{ $opponentClaim }}</strong>
                                @endif
                            @else
                                <span class="spinner-border spinner-border-sm me-1 text-warning"></span> Waiting for opponent claim / admin verification...
                            @endif
                        </div>
                    </div>
                    <div id="disputeReviewAlertBox">
                        @if(in_array($battle->review_status, ['under_review', 'disputed']))
                            <div class="alert alert-warning py-2 mb-0 small fw-bold">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Match is under administrative review / dispute. Payout frozen until verified by platform admin.
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- Form to submit claim --}}
                <div class="ludo-arena-card mb-4" id="submitResultCard">
                    <h5 class="fw-bold text-white mb-2 fs-6">
                        <i class="fa-solid fa-trophy text-warning me-1"></i> Submit Match Result
                    </h5>
                    <p class="small text-secondary mb-3" style="font-size: 11.5px;">
                        Once the Ludo King game concludes, upload your winning screenshot here. You have a 15-minute window.
                    </p>

                    <div id="opponentPreClaimNotice">
                        @if($opponentClaim && $opponentClaim !== 'pending')
                            <div class="alert {{ $opponentClaim === 'won' ? 'alert-warning border-warning' : 'alert-info' }} py-2 px-3 mb-3 small fw-bold animate__animated animate__fadeIn" id="opponentVictoryClaimAlert">
                                @if($opponentClaim === 'won')
                                    <i class="fa-solid fa-bullhorn text-warning me-1"></i> Your opponent has submitted a Victory Claim. Awaiting verification. Please submit your result.
                                @else
                                    <i class="fa-solid fa-bullhorn me-1"></i> Opponent submitted claim: <span class="text-uppercase text-warning">{{ $opponentClaim }}</span>. Please submit yours to finalize or dispute.
                                @endif
                            </div>
                        @endif
                    </div>

                    <form action="{{ route('ludo.battles.result', $battle->id) }}" method="POST" enctype="multipart/form-data" id="submitResultForm">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-light fw-bold small">Your Result Claim <span class="text-danger">*</span></label>
                            <select name="result" id="selectResultClaim" class="form-select bg-dark text-light border-secondary border-opacity-50 small" required>
                                <option value="won">🏆 I Won the Match</option>
                                <option value="lost">❌ I Lost the Match</option>
                                <option value="cancelled">⚠️ Match Cancelled / Not Played</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-light fw-bold small">Winning Screenshot (Optional if opponent confirms)</label>
                            <input type="file" name="evidence" id="inputEvidenceFile" class="form-control bg-dark text-light border-secondary border-opacity-50 small" accept=".jpg,.jpeg,.png,.webp">
                            <small class="text-secondary d-block mt-1" style="font-size: 10.5px;">
                                Upload full end-game screen showing final points to expedite coin payout.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 rounded-3 fw-bold" id="btnSubmitResultClaim">
                            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Submit Result Claim
                        </button>
                    </form>
                </div>
            @endif
        @endif
    </div>

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

            // Audio Synthesizer for Match Alerts
            function playArenaTone(type = 'roomcode') {
                try {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (!AudioCtx) return;
                    const ctx = new AudioCtx();
                    if (ctx.state === 'suspended') ctx.resume();
                    const now = ctx.currentTime;
                    if (type === 'roomcode') {
                        [587.33, 880].forEach((freq, idx) => {
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(freq, now + idx * 0.12);
                            gain.gain.setValueAtTime(0.001, now + idx * 0.12);
                            gain.gain.exponentialRampToValueAtTime(0.18, now + idx * 0.12 + 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.001, now + idx * 0.12 + 0.28);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(now + idx * 0.12);
                            osc.stop(now + idx * 0.12 + 0.3);
                        });
                    }
                } catch (e) {
                    console.debug('Audio error:', e);
                }
            }

            // Live Toast Notification
            function showArenaToast(message, type = 'info') {
                const container = document.getElementById('arenaToastContainer');
                if (!container) return;
                const toast = document.createElement('div');
                const bgClass = type === 'success' ? 'bg-success text-white' : (type === 'warning' ? 'bg-warning text-dark' : 'bg-primary text-white');
                toast.className = `alert ${bgClass} py-2 px-3 mb-2 shadow-lg rounded-pill text-center fw-bold animate__animated animate__fadeInDown`;
                toast.style.pointerEvents = 'auto';
                toast.style.fontSize = '13px';
                toast.innerHTML = `<i class="fa-solid fa-bell me-1"></i> ${message}`;
                container.appendChild(toast);
                setTimeout(() => {
                    toast.style.transition = 'opacity 0.5s ease';
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500);
                }, 4500);
            }

            // Copy Room Code Handler
            function attachCopyListener() {
                const copyBtn = document.getElementById('btnCopyRoomCode');
                const roomCodeText = document.getElementById('roomCodeText');
                const copyBtnText = document.getElementById('copyBtnText');

                if (copyBtn && roomCodeText) {
                    copyBtn.onclick = function() {
                        const code = roomCodeText.textContent.trim();
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(code).then(function() {
                                if (copyBtnText) copyBtnText.textContent = 'Copied!';
                                copyBtn.classList.remove('btn-outline-info');
                                copyBtn.classList.add('btn-success');
                                setTimeout(() => {
                                    if (copyBtnText) copyBtnText.textContent = 'Copy Code';
                                    copyBtn.classList.remove('btn-success');
                                    copyBtn.classList.add('btn-outline-info');
                                }, 2500);
                            });
                        } else {
                            if (copyBtnText) copyBtnText.textContent = 'Copied!';
                            setTimeout(() => {
                                if (copyBtnText) copyBtnText.textContent = 'Copy Code';
                            }, 2500);
                        }
                    };
                }
            }
            attachCopyListener();

            // Dynamic Room Code Box Renderer
            function renderRoomCodeActive(code) {
                const container = document.getElementById('roomCodeDynamicContainer');
                const title = document.getElementById('roomCodeCardTitle');
                if (title) title.textContent = 'Ludo King Room Code';
                if (!container) return;

                container.innerHTML = `
                    <div class="ludo-room-code-box text-center py-3 mb-3 animate__animated animate__fadeIn" style="border: 1px solid rgba(0, 210, 255, 0.4); background: rgba(0, 210, 255, 0.08); border-radius: 8px;">
                        <div class="text-secondary small mb-1" style="font-size: 11px;">ROOM CODE FOR LUDO KING</div>
                        <div class="ludo-room-code-digits fw-bold font-monospace fs-3 text-info" id="roomCodeText">${code}</div>
                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-4 mt-2 fw-bold" id="btnCopyRoomCode">
                            <i class="fa-solid fa-copy me-1"></i> <span id="copyBtnText">Copy Code</span>
                        </button>
                    </div>
                    <div class="d-grid gap-2 mb-3">
                        <a href="ludoking://" class="btn btn-success py-2 rounded-3 fw-bold fs-6 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="fa-solid fa-dice text-warning fs-5"></i>
                            <span>Open Ludo King App</span>
                        </a>
                    </div>
                    <div class="p-2 rounded-3 mb-2" style="background: rgba(14, 42, 92, 0.4); border: 1px dashed rgba(0, 210, 255, 0.3);">
                        <small class="text-secondary d-block" style="font-size: 11px;">
                            📌 <strong class="text-light">How to Play:</strong> Open Ludo King &rarr; Tap 'Play With Friends' &rarr; Select 'Join' &rarr; Paste Code <strong>${code}</strong> &rarr; Play fair!
                        </small>
                    </div>
                `;
                attachCopyListener();
            }

            // AJAX Form Submission for Room Code Setting
            function setupRoomCodeForm() {
                const setRoomCodeForm = document.getElementById('setRoomCodeForm');
                if (setRoomCodeForm) {
                    setRoomCodeForm.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        const input = document.getElementById('inputRoomCode');
                        const submitBtn = document.getElementById('btnSubmitRoomCode');
                        if (!input || !input.value.trim()) return;
                        const code = input.value.trim().toUpperCase();

                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Setting...';
                        }

                        try {
                            const formData = new FormData(setRoomCodeForm);
                            const res = await fetch(setRoomCodeForm.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const data = await res.json();
                            if (data.success) {
                                hasRoomCode = true;
                                renderRoomCodeActive(data.room_code || code);
                                showArenaToast(data.message || 'Room code set successfully!', 'success');
                                playArenaTone('roomcode');
                            } else {
                                alert(data.message || 'Failed to set room code');
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Set';
                                }
                            }
                        } catch (err) {
                            setRoomCodeForm.submit();
                        }
                    });
                }
            }
            setupRoomCodeForm();

            // Dynamic Result Submission and Status Renderers
            function renderMyClaimCard(claimVal, isDisputed = false, opponentClaim = null) {
                const sectionContainer = document.getElementById('matchResultSectionContainer');
                if (!sectionContainer) return;
                const isWon = claimVal === 'won';
                const isLost = claimVal === 'lost';
                const claimTitle = isWon ? '🏆 Victory Claimed (Won)' : (isLost ? '❌ Loss Claimed (Lost)' : '⚠️ Cancellation Claimed');
                const claimNotice = isWon ? 'Your victory claim has been submitted. Awaiting verification.' : 'Your result claim has been recorded.';
                const claimColorClass = isWon ? 'text-success' : (isLost ? 'text-danger' : 'text-warning');

                let opponentText = '<span class="spinner-border spinner-border-sm me-1 text-warning"></span> Waiting for opponent claim / admin verification...';
                if (opponentClaim && opponentClaim !== 'pending') {
                    if (opponentClaim === 'won') {
                        opponentText = '<span class="text-warning fw-bold"><i class="fa-solid fa-bullhorn me-1"></i> Your opponent has submitted a Victory Claim. Awaiting verification.</span>';
                    } else {
                        opponentText = `Opponent Claim: <strong class="text-light text-uppercase">${opponentClaim}</strong>`;
                    }
                }

                sectionContainer.classList.remove('d-none');
                sectionContainer.innerHTML = `
                    <div class="ludo-arena-card mb-4 text-center p-3 animate__animated animate__fadeIn" id="myClaimSubmittedCard">
                        <h5 class="fw-bold text-white mb-2 fs-6">
                            <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> Match Result Status
                        </h5>
                        <div class="p-3 rounded-3 mb-2" style="background: rgba(14, 42, 92, 0.4); border: 1px solid rgba(0, 210, 255, 0.3);">
                            <div class="small text-secondary mb-1">Your Claimed Result:</div>
                            <div class="fw-bold fs-5 text-uppercase ${claimColorClass}" id="myClaimDisplay">
                                ${claimTitle}
                            </div>
                            <div class="small text-light mt-1" id="myClaimNotice">
                                ${claimNotice}
                            </div>
                            <div class="small text-secondary mt-2" id="opponentClaimStatusText">
                                ${opponentText}
                            </div>
                        </div>
                        <div id="disputeReviewAlertBox">
                            ${isDisputed ? `
                                <div class="alert alert-warning py-2 mb-0 small fw-bold animate__animated animate__fadeIn">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Match is under administrative review / dispute. Payout frozen until verified by platform admin.
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }

            function renderMatchCompletedBanner(winnerName, prize, winnerId) {
                const bannerContainer = document.getElementById('matchCompletedBannerContainer');
                const resultSection = document.getElementById('matchResultSectionContainer');
                if (resultSection) resultSection.classList.add('d-none');
                if (bannerContainer) {
                    bannerContainer.innerHTML = `
                        <div class="ludo-arena-card mb-4 text-center py-4 animate__animated animate__fadeIn">
                            <div class="fs-1 text-warning mb-2">🏆</div>
                            <h4 class="fw-bold text-white mb-1">Battle Finished!</h4>
                            <p class="text-success fw-bold mb-2">
                                Winner: ${winnerName || ('Player #' + winnerId)} (Prize: 🪙 ${Number(prize || 0).toLocaleString()})
                            </p>
                            <div class="mt-3">
                                <a href="{{ route('ludo.battles') }}" class="btn btn-outline-info rounded-pill px-4 py-1 small fw-bold">
                                    &larr; Back to Battles Lobby
                                </a>
                            </div>
                        </div>
                    `;
                }
            }

            function renderMatchCancelledBanner(reason, entryFee) {
                const bannerContainer = document.getElementById('matchCompletedBannerContainer');
                const resultSection = document.getElementById('matchResultSectionContainer');
                if (resultSection) resultSection.classList.add('d-none');
                if (bannerContainer) {
                    bannerContainer.innerHTML = `
                        <div class="ludo-arena-card mb-4 text-center py-4 animate__animated animate__fadeIn">
                            <div class="fs-1 text-danger mb-2">🚫</div>
                            <h4 class="fw-bold text-white mb-1">Battle Cancelled</h4>
                            <p class="text-secondary small mb-2">
                                Reason: <span class="text-warning">${reason || 'Cancelled by players'}</span>
                            </p>
                            <div class="text-success small fw-semibold">
                                🪙 ${Number(entryFee || 0).toLocaleString()} points refunded to player wallets.
                            </div>
                            <div class="mt-3">
                                <a href="{{ route('ludo.battles') }}" class="btn btn-outline-info rounded-pill px-4 py-1 small fw-bold">
                                    &larr; Back to Battles Lobby
                                </a>
                            </div>
                        </div>
                    `;
                }
            }

            // AJAX Form Submission for Match Result Claim
            function setupResultForm() {
                const submitResultForm = document.getElementById('submitResultForm');
                if (submitResultForm) {
                    submitResultForm.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        const submitBtn = document.getElementById('btnSubmitResultClaim');
                        const selectClaim = document.getElementById('selectResultClaim');
                        const claimVal = selectClaim ? selectClaim.value : 'won';

                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...';
                        }

                        try {
                            const formData = new FormData(submitResultForm);
                            const res = await fetch(submitResultForm.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            const data = await res.json();
                            if (res.ok && data.success) {
                                showArenaToast(data.message || 'Result claim submitted successfully!', 'success');
                                playArenaTone('roomcode');
                                const isDisputed = (data.status === 'disputed' || data.review_status === 'disputed');
                                renderMyClaimCard(claimVal, isDisputed, null);
                            } else {
                                alert(data.message || 'Failed to submit result claim');
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up me-1"></i> Submit Result Claim';
                                }
                            }
                        } catch (err) {
                            submitResultForm.submit();
                        }
                    });
                }
            }
            setupResultForm();

            // Real-Time Match Arena State Synchronization Engine
            const battleId = {{ (int) $battle->id }};
            let currentStatus = "{{ $battle->status }}";
            let currentReviewStatus = "{{ $battle->review_status ?? 'none' }}";
            let currentRefundStatus = "{{ $battle->refund_status ?? 'none' }}";
            let hasRoomCode = {{ !empty($battle->room_code) ? 'true' : 'false' }};
            let hasNotifiedVictoryClaim = false;

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

                        // Dynamic Coin Balance Update in Header
                        if (typeof data.coins !== 'undefined') {
                            document.querySelectorAll('.user-coins-display').forEach(el => {
                                el.textContent = Number(data.coins).toLocaleString();
                            });
                        }

                        if (data.battle) {
                            const b = data.battle;

                            // 1. Room code set by opponent while this player was waiting
                            if (!hasRoomCode && b.room_code) {
                                hasRoomCode = true;
                                renderRoomCodeActive(b.room_code);
                                showArenaToast('🎮 Opponent shared Room Code: ' + b.room_code, 'success');
                                playArenaTone('roomcode');
                            }

                            // 2. Identify claims
                            const isCreator = (data.user_id === b.creator_id);
                            const myClaim = isCreator ? b.creator_claimed_result : b.joiner_claimed_result;
                            const opponentClaim = isCreator ? b.joiner_claimed_result : b.creator_claimed_result;
                            const oppUserTag = b.opponent_username || b.opponent_name || 'Your opponent';

                            // If opponent submitted claim before current player
                            if ((!myClaim || myClaim === 'pending') && (opponentClaim && opponentClaim !== 'pending')) {
                                const preNotice = document.getElementById('opponentPreClaimNotice');
                                if (preNotice) {
                                    const alertClass = (opponentClaim === 'won') ? 'alert-warning border-warning' : 'alert-info';
                                    const alertMsg = (opponentClaim === 'won')
                                        ? `<i class="fa-solid fa-bullhorn text-warning me-1"></i> Your opponent has submitted a Victory Claim. Awaiting verification. Please submit your result.`
                                        : `<i class="fa-solid fa-bullhorn me-1"></i> Opponent submitted claim: <span class="text-uppercase text-warning">${opponentClaim}</span>. Please submit yours to finalize or dispute.`;

                                    if (!preNotice.querySelector('#opponentVictoryClaimAlert')) {
                                        preNotice.innerHTML = `
                                            <div class="alert ${alertClass} py-2 px-3 mb-3 small fw-bold animate__animated animate__fadeIn" id="opponentVictoryClaimAlert">
                                                ${alertMsg}
                                            </div>
                                        `;
                                    }

                                    if (opponentClaim === 'won' && !hasNotifiedVictoryClaim) {
                                        hasNotifiedVictoryClaim = true;
                                        showArenaToast('Your opponent has submitted a Victory Claim. Awaiting verification.', 'warning');
                                        playArenaTone('roomcode');
                                    }
                                }
                            }

                            // If current player has already submitted claim, update opponent claim label
                            if (myClaim && myClaim !== 'pending') {
                                const oppStatusText = document.getElementById('opponentClaimStatusText');
                                if (oppStatusText && opponentClaim && opponentClaim !== 'pending') {
                                    if (opponentClaim === 'won') {
                                        oppStatusText.innerHTML = `<span class="text-warning fw-bold"><i class="fa-solid fa-bullhorn me-1"></i> Your opponent has submitted a Victory Claim. Awaiting verification.</span>`;
                                    } else {
                                        oppStatusText.innerHTML = `Opponent Claim: <strong class="text-light text-uppercase">${opponentClaim}</strong>`;
                                    }
                                }
                            }

                            // 3. Administrative review or dispute status changed
                            if (b.review_status && b.review_status !== currentReviewStatus) {
                                currentReviewStatus = b.review_status;
                                showArenaToast('Review Status: ' + currentReviewStatus.replace('_', ' ').toUpperCase(), 'warning');
                                playArenaTone('roomcode');

                                const disputeBox = document.getElementById('disputeReviewAlertBox');
                                if (disputeBox && (b.review_status === 'under_review' || b.review_status === 'disputed')) {
                                    disputeBox.innerHTML = `
                                        <div class="alert alert-warning py-2 mb-0 small fw-bold animate__animated animate__fadeIn">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i> Match is under administrative review / dispute. Payout frozen until verified by platform admin.
                                        </div>
                                    `;
                                }
                            }

                            // 4. Match status transitioned (e.g. running -> completed / cancelled)
                            if (b.status !== currentStatus) {
                                currentStatus = b.status;
                                const statusText = document.getElementById('arenaMatchStatusText');
                                if (statusText) statusText.textContent = currentStatus;

                                const badgeContainer = document.getElementById('arenaHeaderStatusBadgeContainer');
                                if (badgeContainer) {
                                    if (currentStatus === 'running') {
                                        badgeContainer.innerHTML = '<span class="badge bg-danger text-white border border-danger px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;"><span class="live-dot d-inline-block me-1" style="width: 6px; height: 6px; background: #fff; border-radius: 50%;"></span>LIVE BATTLE</span>';
                                        const waitingCard = document.getElementById('waitingCreatorCard');
                                        if (waitingCard) waitingCard.style.display = 'none';
                                        showArenaToast('🎮 Match Started! Waiting for Room Code.', 'success');
                                    } else if (currentStatus === 'completed') {
                                        badgeContainer.innerHTML = '<span class="badge bg-success text-white px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;"><i class="fa-solid fa-check me-1"></i>COMPLETED</span>';
                                        showArenaToast('Match Completed! Outcome settled.', 'info');
                                    } else if (currentStatus === 'cancelled') {
                                        badgeContainer.innerHTML = '<span class="badge bg-secondary text-white px-2 py-1" id="arenaHeaderStatusBadge" style="font-size: 10px;"><i class="fa-solid fa-ban me-1"></i>CANCELLED</span>';
                                        showArenaToast('Match Cancelled and Stakes Refunded.', 'warning');
                                    }
                                }

                                if (currentStatus === 'completed') {
                                    renderMatchCompletedBanner(b.winner_name, b.prize, b.winner_id);
                                    playArenaTone('roomcode');
                                } else if (currentStatus === 'cancelled') {
                                    renderMatchCancelledBanner(b.cancellation_reason, b.entry_fee);
                                }
                            }

                            // 5. Refund status changed
                            if (b.refund_status && b.refund_status !== currentRefundStatus) {
                                currentRefundStatus = b.refund_status;
                                showArenaToast('Refund Status: ' + currentRefundStatus.toUpperCase(), 'warning');
                            }
                        }
                    }
                } catch (err) {
                    console.debug("Arena sync poll:", err);
                } finally {
                    isPolling = false;
                }
            }, 2500);

                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden && !isPolling) {
                        fetch("{{ route('ludo.battles.syncState') }}?battle_id=" + battleId, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        }).then(r => r.json()).then(d => {
                            if (d.battle) {
                                if (!hasRoomCode && d.battle.room_code) {
                                    hasRoomCode = true;
                                    renderRoomCodeActive(d.battle.room_code);
                                    showArenaToast('🎮 Opponent shared Room Code: ' + d.battle.room_code, 'success');
                                    playArenaTone('roomcode');
                                }
                                if (d.battle.status !== currentStatus) {
                                    currentStatus = d.battle.status;
                                    const statusText = document.getElementById('arenaMatchStatusText');
                                    if (statusText) statusText.textContent = currentStatus;
                                }
                            }
                        }).catch(() => {});
                    }
                });
        });
    </script>
    @endpush
@endsection
