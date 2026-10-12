@extends('layouts.admin')

@section('title', 'Audit Match — ' . $match->battle_uid)
@section('page_title', 'Match Audit Dossier: ' . $match->battle_uid)

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.ludo.matches.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Back to Matches
        </a>
        <div class="d-flex gap-2">
            <span class="badge {{ $match->status === 'completed' ? 'bg-success' : ($match->status === 'running' ? 'bg-primary' : ($match->status === 'cancelled' ? 'bg-danger' : 'bg-secondary')) }} fs-6">
                {{ strtoupper($match->status) }}
            </span>
            <span class="badge {{ $match->review_status === 'disputed' ? 'bg-danger' : ($match->review_status === 'under_review' ? 'bg-warning text-dark' : 'bg-dark border border-secondary text-info') }} fs-6">
                {{ strtoupper(str_replace('_', ' ', $match->review_status)) }}
            </span>
        </div>
    </div>

    {{-- Match Overview Cards --}}
    <div class="row g-3 mb-4">
        {{-- Participants Details --}}
        <div class="col-12 col-lg-8">
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
                            <h6 class="text-white fw-bold mb-1">{{ $match->creator->name }}</h6>
                            <div class="text-secondary small">{{ $match->creator->email }}</div>
                            <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between">
                                <span class="text-secondary small">Submitted Claim:</span>
                                <span class="badge bg-dark border border-secondary text-info">{{ strtoupper($match->creator_claimed_result ?? 'PENDING') }}</span>
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
                            <h6 class="text-white fw-bold mb-1">{{ $match->joiner->name ?? 'Awaiting Opponent' }}</h6>
                            <div class="text-secondary small">{{ $match->joiner->email ?? 'No email' }}</div>
                            <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between">
                                <span class="text-secondary small">Submitted Claim:</span>
                                <span class="badge bg-dark border border-secondary text-info">{{ strtoupper($match->joiner_claimed_result ?? 'PENDING') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timers & Stakes --}}
                <div class="row g-2 mt-3 pt-3 border-top border-secondary border-opacity-25 text-center">
                    <div class="col-3">
                        <div class="text-secondary small">Entry Fee</div>
                        <div class="text-white fw-bold">🪙 {{ $match->entry_fee }}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-secondary small">Prize Pool</div>
                        <div class="text-success fw-bold">🪙 {{ $match->prize }}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-secondary small">Submission Deadline</div>
                        <div class="text-warning fw-bold small">
                            {{ $match->submission_deadline_at ? $match->submission_deadline_at->format('h:i A') : 'N/A' }}
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-secondary small">Winner Awarded</div>
                        <div class="text-info fw-bold small">
                            {{ $match->winner ? $match->winner->name : 'Unsettled' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Administrative Cancellation Form --}}
        <div class="col-12 col-lg-4">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-ban text-danger me-1"></i> Administrative Cancellation
                </h6>

                @if($match->status !== 'cancelled')
                    <form action="{{ route('admin.ludo.matches.cancel', $match->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold mb-1">
                                Mandatory Cancellation Rationale <span class="text-danger">*</span>
                            </label>
                            <textarea name="reason" rows="3" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Specify why this match is being cancelled (e.g. invalid room code, double disconnect)..." required minlength="5"></textarea>
                            <small class="text-secondary d-block mt-1">Both participants will receive a full 100% entry fee refund recorded in their ledger.</small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger w-100 rounded-pill fw-bold" onclick="return confirm('Cancel this match and refund entry fees to both players?')">
                            Cancel Match & Refund Both
                        </button>
                    </form>
                @else
                    <div class="alert alert-secondary bg-dark text-secondary small mb-0">
                        This match was cancelled. Points were refunded to participants.
                    </div>
                @endif

                @if($match->dispute)
                    <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
                        <div class="text-danger small fw-bold mb-1">
                            <i class="fa-solid fa-gavel me-1"></i> Linked Dispute: {{ $match->dispute->dispute_uid }}
                        </div>
                        <a href="{{ route('ludo.dispute.view', $match->dispute->id) }}" class="btn btn-sm btn-outline-danger w-100 rounded-pill">
                            Open Dispute Dossier &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
