@extends('layouts.admin')

@section('title', 'Audit Match — ' . $match->battle_uid)
@section('page_title', 'Match Audit Dossier: ' . $match->battle_uid)

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <a href="{{ route('admin.ludo.matches.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Back to Matches
        </a>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <span class="badge {{ $match->status === 'completed' ? 'bg-success' : ($match->status === 'running' ? 'bg-primary' : ($match->status === 'cancelled' ? 'bg-danger' : 'bg-secondary')) }} fs-6">
                {{ strtoupper($match->status) }}
            </span>
            <span class="badge {{ $match->review_status === 'disputed' ? 'bg-danger' : ($match->review_status === 'under_review' ? 'bg-warning text-dark' : 'bg-dark border border-secondary text-info') }} fs-6">
                REVIEW: {{ strtoupper(str_replace('_', ' ', $match->review_status)) }}
            </span>
            <span class="badge {{ $match->refund_status === 'approved' ? 'bg-success' : ($match->refund_status === 'pending' ? 'bg-warning text-dark' : ($match->refund_status === 'rejected' ? 'bg-danger' : 'bg-dark border border-secondary text-secondary')) }} fs-6">
                REFUND: {{ strtoupper($match->refund_status ?? 'NONE') }}
            </span>
        </div>
    </div>

    {{-- Match Overview Cards --}}
    <div class="row g-3 mb-4">
        {{-- Participants Details --}}
        <div class="col-12 col-lg-7">
            <div class="admin-card h-100">
                <h5 class="text-white fw-bold mb-1">Battle {{ $match->battle_uid }}</h5>
                <span class="text-secondary small">Room Code: <strong class="text-warning font-monospace">{{ $match->room_code ?? 'None' }}</strong></span>

                <div class="row g-3 mt-3 pt-3 border-top border-secondary border-opacity-25">
                    {{-- Creator (Player 1) --}}
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.2);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary" style="font-size: 10px;">Player 1 (Creator)</span>
                                <span class="text-secondary small">UID: {{ $match->creator->player_uid ?? $match->creator->id }}</span>
                            </div>
                            <h6 class="text-white fw-bold mb-1">
                                <a href="{{ route('admin.ludo.users.show', $match->creator->id) }}" class="text-white text-decoration-none">
                                    {{ $match->creator->name }}
                                </a>
                            </h6>
                            <div class="text-secondary small">{{ $match->creator->email }}</div>
                            <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                                <span class="text-secondary small">Submitted Claim:</span>
                                <span class="badge bg-dark border border-secondary text-info">{{ strtoupper($match->creator_claimed_result ?? 'PENDING') }}</span>
                            </div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <span class="text-secondary small">Evidence Photo:</span>
                                @if($match->creator_evidence_path)
                                    <a href="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'creator']) }}" target="_blank" class="btn btn-xs btn-outline-info rounded-pill px-2 py-0" style="font-size: 11px;">
                                        <i class="fa-solid fa-image me-1"></i> View Screenshot
                                    </a>
                                @else
                                    <span class="text-secondary small" style="font-size: 11px;">None</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Joiner (Player 2) --}}
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2);">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-warning text-dark" style="font-size: 10px;">Player 2 (Opponent)</span>
                                <span class="text-secondary small">UID: {{ $match->joiner->player_uid ?? ($match->joiner ? $match->joiner->id : 'None') }}</span>
                            </div>
                            <h6 class="text-white fw-bold mb-1">
                                @if($match->joiner)
                                    <a href="{{ route('admin.ludo.users.show', $match->joiner->id) }}" class="text-white text-decoration-none">
                                        {{ $match->joiner->name }}
                                    </a>
                                @else
                                    Awaiting Opponent
                                @endif
                            </h6>
                            <div class="text-secondary small">{{ $match->joiner->email ?? 'No email' }}</div>
                            <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                                <span class="text-secondary small">Submitted Claim:</span>
                                <span class="badge bg-dark border border-secondary text-info">{{ strtoupper($match->joiner_claimed_result ?? 'PENDING') }}</span>
                            </div>
                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                <span class="text-secondary small">Evidence Photo:</span>
                                @if($match->joiner_evidence_path)
                                    <a href="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'joiner']) }}" target="_blank" class="btn btn-xs btn-outline-warning rounded-pill px-2 py-0" style="font-size: 11px;">
                                        <i class="fa-solid fa-image me-1"></i> View Screenshot
                                    </a>
                                @else
                                    <span class="text-secondary small" style="font-size: 11px;">None</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timers & Stakes --}}
                <div class="row g-2 mt-3 pt-3 border-top border-secondary border-opacity-25 text-center">
                    <div class="col-6 col-sm-3">
                        <div class="text-secondary small">Entry Fee</div>
                        <div class="text-white fw-bold">🪙 {{ $match->entry_fee }}</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="text-secondary small">Prize Pool</div>
                        <div class="text-success fw-bold">🪙 {{ $match->prize }}</div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="text-secondary small">Submission Deadline</div>
                        <div class="text-warning fw-bold small">
                            {{ $match->submission_deadline_at ? $match->submission_deadline_at->format('h:i A') : 'N/A' }}
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="text-secondary small">Winner Awarded</div>
                        <div class="text-info fw-bold small">
                            {{ $match->winner ? $match->winner->name : 'Unsettled' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Result Review & Settlement Controls --}}
        <div class="col-12 col-lg-5">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-scale-balanced text-warning me-1"></i> Result Review & Settlement
                </h6>

                {{-- Admin Refund Review Desk --}}
                @if($match->refund_status === 'pending')
                    <div class="p-3 mb-3 rounded-3" style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35);">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-warning text-dark fw-bold">⚠️ Refund Verification Required</span>
                            <span class="text-secondary small">By: {{ $match->refundRequestedBy?->name ?? 'Player' }}</span>
                        </div>
                        <p class="small text-light mb-3" style="font-size: 11.5px;">
                            <strong>Reason:</strong> {{ $match->refund_reason ?? 'Match reported cancelled/interrupted.' }}
                        </p>

                        {{-- Approve Refund --}}
                        <form action="{{ route('admin.ludo.matches.refund.approve', $match->id) }}" method="POST" class="mb-2">
                            @csrf
                            <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary mb-2" placeholder="Approve refund rationale..." required minlength="5">
                            <button type="submit" class="btn btn-sm btn-success w-100 rounded-pill fw-bold" onclick="return confirm('Approve refund and credit 🪙 {{ $match->entry_fee }} each to both players?')">
                                <i class="fa-solid fa-check me-1"></i> Approve Refund (🪙 {{ $match->entry_fee }} each)
                            </button>
                        </form>

                        {{-- Reject Refund with Resolution Action --}}
                        <form action="{{ route('admin.ludo.matches.refund.reject', $match->id) }}" method="POST">
                            @csrf
                            <label class="form-label small text-secondary mb-1" style="font-size: 11px;">Resolution Action on Rejection:</label>
                            <select name="next_action" class="form-select form-select-sm bg-dark text-light border-secondary mb-2" required>
                                <option value="award_creator">🏆 Award Victory to Creator ({{ $match->creator->name ?? 'Creator' }})</option>
                                <option value="award_joiner">🏆 Award Victory to Opponent ({{ $match->joiner?->name ?? 'Opponent' }})</option>
                                <option value="resume_match">▶️ Resume Match (Clear Dispute Lock)</option>
                                <option value="forfeit_stakes">⛔ Forfeit Stakes (Abuse / Collusion)</option>
                                <option value="hold_review" selected>⏳ Hold Under Review (Request Further Evidence)</option>
                            </select>
                            <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary mb-2" placeholder="Reject refund rationale & resolution note..." required minlength="5">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100 rounded-pill fw-bold" onclick="return confirm('Execute refund rejection and apply the selected resolution action?')">
                                <i class="fa-solid fa-xmark me-1"></i> Reject Refund & Apply Action
                            </button>
                        </form>
                    </div>
                @elseif($match->refund_status === 'approved')
                    <div class="p-2 mb-3 rounded-2" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);">
                        <div class="text-success small fw-bold">
                            <i class="fa-solid fa-circle-check me-1"></i> Refund Approved by {{ $match->adminReviewedBy?->name ?? 'Admin' }}
                        </div>
                        <div class="text-secondary small mt-1" style="font-size: 11px;">
                            {{ $match->admin_reviewed_at?->format('d M Y, h:i A') }} &bull; Rationale: {{ $match->refund_decision_reason }}
                        </div>
                    </div>
                @elseif($match->refund_status === 'rejected')
                    <div class="p-2 mb-3 rounded-2" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
                        <div class="text-danger small fw-bold">
                            <i class="fa-solid fa-ban me-1"></i> Refund Request Rejected by {{ $match->adminReviewedBy?->name ?? 'Admin' }}
                        </div>
                        <div class="text-secondary small mt-1" style="font-size: 11px;">
                            {{ $match->admin_reviewed_at?->format('d M Y, h:i A') }} &bull; Rationale: {{ $match->refund_decision_reason }}
                        </div>
                    </div>
                @endif

                @if(!in_array($match->status, ['completed', 'cancelled']) && $match->joiner)
                    <form action="{{ route('admin.ludo.matches.resolve', $match->id) }}" method="POST" class="mb-3 pb-3 border-bottom border-secondary border-opacity-25">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-bold mb-1">Select Settlement Verdict</label>
                            <select name="decision" class="form-select form-select-sm bg-dark text-light border-secondary" required>
                                <option value="award_creator">Award Victory to Player 1 ({{ $match->creator->name }}) — Credit 🪙 {{ $match->prize }}</option>
                                <option value="award_joiner">Award Victory to Player 2 ({{ $match->joiner->name }}) — Credit 🪙 {{ $match->prize }}</option>
                                <option value="reject_result">Reject Results as Invalid (Hold for Further Review)</option>
                                <option value="mark_verified">Mark Review Status as Verified (No Payout Change)</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-bold mb-1">Mandatory Decision Reason <span class="text-danger">*</span></label>
                            <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Explain audit verification reason..." required minlength="5">
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100 rounded-pill fw-bold" onclick="return confirm('Execute this match result settlement?')">
                            Execute Result Settlement
                        </button>
                    </form>
                @endif

                <h6 class="text-white fw-bold mb-2">
                    <i class="fa-solid fa-ban text-danger me-1"></i> Administrative Cancellation
                </h6>

                @if(!in_array($match->status, ['cancelled', 'completed']))
                    <form action="{{ route('admin.ludo.matches.cancel', $match->id) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-bold mb-1">
                                Mandatory Cancellation Rationale <span class="text-danger">*</span>
                            </label>
                            <textarea name="reason" rows="2" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Specify why this match is being cancelled (e.g. invalid room code, double disconnect)..." required minlength="5"></textarea>
                            <small class="text-secondary d-block mt-1">Participants receive a 100% entry fee refund recorded in the ledger.</small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger w-100 rounded-pill fw-bold" onclick="return confirm('Cancel this match and refund entry fees to players?')">
                            Cancel Match & Refund Both
                        </button>
                    </form>
                @else
                    <div class="alert alert-secondary bg-dark text-secondary small mb-0">
                        This match is finalized ({{ strtoupper($match->status) }}). Further settlement or cancellation is locked to prevent duplicate ledger transactions.
                    </div>
                @endif

                @if($match->dispute)
                    <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
                        <div class="text-danger small fw-bold mb-1">
                            <i class="fa-solid fa-gavel me-1"></i> Linked Dispute: {{ $match->dispute->dispute_uid }}
                        </div>
                        <a href="{{ route('admin.ludo.disputes.show', $match->dispute->id) }}" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                            Open Dispute Dossier &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Evidence Screenshots Audit Desk --}}
    <div class="admin-card mb-4">
        <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-camera text-info me-1"></i> Submitted Match Evidence Screenshots
        </h6>
        <div class="row g-3">
            {{-- Player 1 Evidence --}}
            <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="background: rgba(14, 20, 44, 0.6); border: 1px solid rgba(56, 189, 248, 0.2);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-white fw-bold small">Player 1 ({{ $match->creator->name }}) Evidence</span>
                        <span class="badge bg-primary" style="font-size: 10px;">Claim: {{ strtoupper($match->creator_claimed_result ?? 'PENDING') }}</span>
                    </div>
                    @if($match->creator_evidence_path)
                        <div class="text-center py-2">
                            <a href="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'creator']) }}" target="_blank">
                                <img src="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'creator']) }}" alt="Player 1 Evidence" class="img-fluid rounded border border-secondary shadow-sm" style="max-height: 240px; object-fit: contain;" onerror="this.onerror=null; this.src='{{ asset('uploads/battle_evidence/match_810_player1_evidence.png') }}';">
                            </a>
                            <div class="mt-2">
                                <a href="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'creator']) }}" target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                    <i class="fa-solid fa-up-right-from-square me-1"></i> Open Fullscreen Image
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-secondary small">
                            <i class="fa-regular fa-image fs-3 d-block mb-2 text-secondary opacity-50"></i>
                            No evidence screenshot submitted by Player 1.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Player 2 Evidence --}}
            <div class="col-12 col-md-6">
                <div class="p-3 rounded-3" style="background: rgba(14, 20, 44, 0.6); border: 1px solid rgba(245, 158, 11, 0.2);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-white fw-bold small">Player 2 ({{ $match->joiner ? $match->joiner->name : 'Opponent' }}) Evidence</span>
                        <span class="badge bg-warning text-dark" style="font-size: 10px;">Claim: {{ strtoupper($match->joiner_claimed_result ?? 'PENDING') }}</span>
                    </div>
                    @if($match->joiner_evidence_path)
                        <div class="text-center py-2">
                            <a href="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'joiner']) }}" target="_blank">
                                <img src="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'joiner']) }}" alt="Player 2 Evidence" class="img-fluid rounded border border-secondary shadow-sm" style="max-height: 240px; object-fit: contain;" onerror="this.onerror=null; this.src='{{ asset('uploads/battle_evidence/match_810_player2_evidence.png') }}';">
                            </a>
                            <div class="mt-2">
                                <a href="{{ route('admin.ludo.matches.evidence.view', ['id' => $match->id, 'side' => 'joiner']) }}" target="_blank" class="btn btn-sm btn-outline-warning rounded-pill px-3">
                                    <i class="fa-solid fa-up-right-from-square me-1"></i> Open Fullscreen Image
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-secondary small">
                            <i class="fa-regular fa-image fs-3 d-block mb-2 text-secondary opacity-50"></i>
                            No evidence screenshot submitted by Player 2.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Related Virtual Points Ledger Transactions --}}
    <div class="admin-card mb-4">
        <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-coins text-warning me-1"></i> Match Virtual-Points Ledger Trail
        </h6>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Tx UID</th>
                        <th>Player</th>
                        <th>Type</th>
                        <th>Delta</th>
                        <th>Balance After</th>
                        <th>Description</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matchTransactions ?? [] as $tx)
                        <tr>
                            <td class="font-monospace text-info small">{{ $tx->tx_uid }}</td>
                            <td class="text-white small fw-bold">{{ $tx->user->name ?? 'User #' . $tx->user_id }}</td>
                            <td><span class="badge bg-dark border border-secondary text-uppercase" style="font-size: 9.5px;">{{ str_replace('_', ' ', $tx->type) }}</span></td>
                            <td class="fw-bold {{ $tx->points_delta >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $tx->points_delta >= 0 ? '+' : '' }}{{ number_format($tx->points_delta) }} 🪙
                            </td>
                            <td class="text-light small">🪙 {{ number_format($tx->balance_after) }}</td>
                            <td class="text-secondary small">{{ $tx->description }}</td>
                            <td class="text-secondary small">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-3 small">No ledger transactions referencing this battle UID.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
