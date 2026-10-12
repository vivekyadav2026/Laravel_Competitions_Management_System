@php
    $p1 = $running->creator->name ?? 'Player_1';
    $p2 = $running->joiner->name ?? 'Player_2';
@endphp
<div class="ludo-running-card" data-battle-id="{{ $running->id }}">
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

    {{-- Participant Result & Dispute Actions --}}
    @auth
        @if(Auth::id() === $running->creator_id || Auth::id() === $running->joiner_id)
            <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 py-1 fw-bold" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#resultModal{{ $running->id }}">
                    <i class="fa-solid fa-trophy me-1"></i> Submit Result
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1" style="font-size: 11px;" data-bs-toggle="modal" data-bs-target="#disputeModal{{ $running->id }}">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Dispute
                </button>
            </div>

            {{-- Result Modal --}}
            <div class="modal fade" id="resultModal{{ $running->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered px-3">
                    <div class="modal-content ludo-modal-dialog">
                        <div class="modal-header border-secondary border-opacity-25 pb-2">
                            <h5 class="modal-title fw-bold fs-6">Submit Match Result (15-Min Window)</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('ludo.battles.result', $running->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body text-secondary small py-3">
                                <div class="mb-2">
                                    <label class="form-label text-light fw-bold small">Your Result Claim <span class="text-danger">*</span></label>
                                    <select name="result" class="form-select bg-dark text-light border-secondary border-opacity-50 small" required>
                                        <option value="won">I Won the Match</option>
                                        <option value="lost">I Lost the Match</option>
                                        <option value="cancelled">Match Not Started / Cancelled</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-light fw-bold small">Winning Screenshot (Optional if opponent agrees)</label>
                                    <input type="file" name="evidence" class="form-control bg-dark text-light border-secondary border-opacity-50 small" accept=".jpg,.jpeg,.png,.webp">
                                    <small class="text-secondary d-block mt-1">Upload clear winning screen to guarantee fast verification.</small>
                                </div>
                            </div>
                            <div class="modal-footer border-secondary border-opacity-25 pt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-4 fw-bold">Submit Claim</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Dispute Modal --}}
            <div class="modal fade" id="disputeModal{{ $running->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered px-3">
                    <div class="modal-content ludo-modal-dialog">
                        <div class="modal-header border-secondary border-opacity-25 pb-2">
                            <h5 class="modal-title fw-bold fs-6 text-danger">
                                <i class="fa-solid fa-gavel me-1"></i> Open Dispute (Battle {{ $running->battle_uid }})
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('ludo.dispute.open', $running->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body text-secondary small py-3">
                                <div class="mb-2">
                                    <label class="form-label text-light fw-bold small">Dispute Reason <span class="text-danger">*</span></label>
                                    <select name="reason_category" class="form-select bg-dark text-light border-secondary border-opacity-50 small" required>
                                        <option value="wrong_result">Opponent Submitted False Result</option>
                                        <option value="opponent_left">Opponent Left Mid-Match</option>
                                        <option value="fake_screenshot">Fake / Edited Screenshot Uploaded</option>
                                        <option value="app_crash">Game / Server App Crashed</option>
                                        <option value="timer_expired">Timer Expired / No Room Code</option>
                                        <option value="other">Other Violation</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-light fw-bold small">Explanation <span class="text-danger">*</span></label>
                                    <textarea name="description" rows="3" class="form-control bg-dark text-light border-secondary border-opacity-50 small" placeholder="Explain the match incident clearly..." required minlength="10"></textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-light fw-bold small">Evidence Screenshot/Video <span class="text-danger">*</span></label>
                                    <input type="file" name="evidence" class="form-control bg-dark text-light border-secondary border-opacity-50 small" accept=".jpg,.jpeg,.png,.webp,.mp4" required>
                                    <small class="text-secondary d-block mt-1">Stored securely in private storage for compliance review.</small>
                                </div>
                            </div>
                            <div class="modal-footer border-secondary border-opacity-25 pt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4 fw-bold">Lodge Dispute</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endauth
</div>
