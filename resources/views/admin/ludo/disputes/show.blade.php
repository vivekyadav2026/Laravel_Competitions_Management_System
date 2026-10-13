@extends('layouts.admin')

@section('title', 'Dispute Dossier — ' . $dispute->dispute_uid)
@section('page_title', 'Dispute Resolution Workbench: ' . $dispute->dispute_uid)

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.ludo.disputes.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Back to Dispute Desk
        </a>
        <div class="d-flex gap-2">
            @if($dispute->battle)
                <a href="{{ route('admin.ludo.matches.show', $dispute->battle->id) }}" class="btn btn-sm btn-outline-info rounded-pill px-3">
                    <i class="fa-solid fa-gamepad me-1"></i> Battle {{ $dispute->battle->battle_uid }}
                </a>
            @endif
            <span class="badge {{ $dispute->status === 'open' ? 'bg-danger' : ($dispute->status === 'resolved' ? 'bg-success' : 'bg-warning text-dark') }} fs-6">
                {{ strtoupper(str_replace('_', ' ', $dispute->status)) }}
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Dispute & Participants Details --}}
        <div class="col-12 col-lg-7">
            <div class="admin-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="text-white fw-bold mb-0">{{ $dispute->dispute_uid }}</h5>
                    <span class="badge bg-dark border border-secondary text-warning">
                        {{ ucwords(str_replace('_', ' ', $dispute->reason_category)) }}
                    </span>
                </div>
                <div class="text-secondary small mb-3">
                    Opened {{ $dispute->created_at->format('d M Y, h:i A') }}
                    @if($dispute->battle)
                        &bull; Stakes: 🪙 {{ $dispute->battle->entry_fee }} Entry / <strong class="text-success">🪙 {{ $dispute->battle->prize }} Prize</strong>
                    @endif
                </div>

                <div class="p-3 rounded-3 mb-3" style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08);">
                    <div class="text-secondary small fw-bold mb-1">Complainant Statement:</div>
                    <div class="text-light small">{{ $dispute->description }}</div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25);">
                            <span class="badge bg-info text-dark mb-1" style="font-size: 10px;">Complainant</span>
                            <h6 class="text-white fw-bold mb-0">
                                @if($dispute->openedBy)
                                    <a href="{{ route('admin.ludo.users.show', $dispute->openedBy->id) }}" class="text-white text-decoration-none">
                                        {{ $dispute->openedBy->name }}
                                    </a>
                                @else
                                    Player #{{ $dispute->opened_by_user_id }}
                                @endif
                            </h6>
                            <div class="text-secondary small">{{ $dispute->openedBy->email ?? '' }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25);">
                            <span class="badge bg-warning text-dark mb-1" style="font-size: 10px;">Opponent</span>
                            <h6 class="text-white fw-bold mb-0">
                                @if($dispute->againstUser)
                                    <a href="{{ route('admin.ludo.users.show', $dispute->againstUser->id) }}" class="text-white text-decoration-none">
                                        {{ $dispute->againstUser->name }}
                                    </a>
                                @else
                                    Player #{{ $dispute->against_user_id }}
                                @endif
                            </h6>
                            <div class="text-secondary small">{{ $dispute->againstUser->email ?? '' }}</div>
                        </div>
                    </div>
                </div>

                @if($dispute->admin_decision_reason)
                    <div class="mt-3 p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-50">
                        <div class="text-success small fw-bold mb-1">
                            <i class="fa-solid fa-gavel me-1"></i> Recorded Administrative Decision
                        </div>
                        <div class="text-white small">{{ $dispute->admin_decision_reason }}</div>
                        @if($dispute->resolver)
                            <div class="text-secondary small mt-1">
                                Resolved by <strong>{{ $dispute->resolver->name }}</strong>
                                {{ $dispute->resolved_at ? 'on ' . $dispute->resolved_at->format('d M Y, h:i A') : '' }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Resolution Decision Form --}}
        <div class="col-12 col-lg-5">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-gavel text-warning me-1"></i> Adjudication Verdict
                </h6>

                @if($dispute->status !== 'resolved')
                    <form action="{{ route('ludo.admin.dispute.resolve', $dispute->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-light small fw-bold">Select Adjudication Decision <span class="text-danger">*</span></label>
                            <select name="decision" class="form-select bg-dark text-light border-secondary small" required>
                                @if($dispute->battle)
                                    <option value="award_creator">Award Victory to Creator ({{ $dispute->battle->creator->name ?? 'P1' }}) — Credit 🪙 {{ $dispute->battle->prize }}</option>
                                    <option value="award_joiner">Award Victory to Joiner ({{ $dispute->battle->joiner->name ?? 'P2' }}) — Credit 🪙 {{ $dispute->battle->prize }}</option>
                                    <option value="refund_both">Cancel Match & Refund Entry Fee to Both Players</option>
                                @endif
                                <option value="request_evidence">Request Additional Evidence from Players</option>
                                <option value="reject_dispute">Reject Dispute Claim (Maintain Existing Match State)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-light small fw-bold">Mandatory Written Decision Rationale <span class="text-danger">*</span></label>
                            <textarea name="admin_reason" rows="3" class="form-control bg-dark text-light border-secondary small" placeholder="Provide detailed reasoning based on inspected screenshot/video evidence (minimum 10 characters)..." required minlength="10"></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold" onclick="return confirm('Confirm and execute this dispute resolution verdict?')">
                            <i class="fa-solid fa-check-double me-1"></i> Execute Dispute Verdict
                        </button>
                    </form>
                @else
                    <div class="alert alert-secondary bg-dark text-secondary small mb-0">
                        This dispute has been finalized (<strong>RESOLVED</strong>). Further adjudication is locked to protect ledger and result integrity.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Submitted Evidence Files --}}
    <div class="admin-card mb-4">
        <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-file-shield text-info me-1"></i> Protected Evidence Submissions ({{ $dispute->evidences->count() }})
        </h6>

        @if($dispute->evidences->count() > 0)
            <div class="row g-3">
                @foreach($dispute->evidences as $ev)
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3 d-flex justify-content-between align-items-center" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08);">
                            <div>
                                <div class="text-white small fw-bold">
                                    {{ $ev->user->name ?? 'Player #' . $ev->user_id }}
                                    <span class="badge bg-dark border border-secondary text-secondary ms-1" style="font-size: 9px;">{{ $ev->file_mime ?? 'File' }}</span>
                                </div>
                                <div class="text-secondary small">{{ $ev->notes ?? 'Evidence attachment' }}</div>
                                <div class="text-secondary" style="font-size: 10px;">Uploaded {{ $ev->created_at->format('d M Y, h:i A') }}</div>
                            </div>
                            <a href="{{ route('ludo.dispute.evidence.download', $ev->id) }}" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                <i class="fa-solid fa-download me-1"></i> Download
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-secondary py-3 small">No supplementary evidence files attached.</div>
        @endif
    </div>
@endsection
