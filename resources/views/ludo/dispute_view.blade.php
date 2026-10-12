@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Dispute ' . $dispute->dispute_uid)

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('ludo.history') }}" class="btn btn-sm btn-outline-secondary text-light rounded-pill py-1 px-3" style="font-size: 12px;">
            &larr; Back to History
        </a>
        <span class="badge {{ $dispute->status === 'resolved' ? 'bg-success' : ($dispute->status === 'rejected' ? 'bg-danger' : ($dispute->status === 'more_evidence_requested' ? 'bg-warning text-dark' : 'bg-primary')) }}" style="font-size: 11px; text-transform: uppercase;">
            {{ str_replace('_', ' ', $dispute->status) }}
        </span>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="alert alert-success bg-success bg-opacity-25 text-success border border-success border-opacity-50 small rounded-3 mb-3 py-2 px-3">
            <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 small rounded-3 mb-3 py-2 px-3">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ session('warning') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 small rounded-3 mb-3 py-2 px-3">
            <i class="fa-solid fa-circle-exclamation me-1"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Main Dispute Dossier Card --}}
    <div class="p-3 rounded-3 mb-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #ef4444; box-shadow: 0 4px 18px rgba(239, 68, 68, 0.15);">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-danger fw-bold font-monospace fs-6">
                <i class="fa-solid fa-gavel me-1"></i> {{ $dispute->dispute_uid }}
            </span>
            <span class="text-secondary small" style="font-size: 11px;">
                Battle: <strong class="text-info">{{ $dispute->battle->battle_uid }}</strong>
            </span>
        </div>

        <div class="text-white small mb-3" style="font-size: 12.5px;">
            <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                <span class="text-secondary">Opened By:</span>
                <span class="fw-bold text-light">{{ $dispute->openedBy->name ?? 'Participant 1' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                <span class="text-secondary">Opponent:</span>
                <span class="fw-bold text-light">{{ $dispute->againstUser->name ?? 'Participant 2' }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                <span class="text-secondary">Reason Category:</span>
                <span class="fw-bold text-warning">{{ ucwords(str_replace('_', ' ', $dispute->reason_category)) }}</span>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                <span class="text-secondary">Prize Pool at Stake:</span>
                <span class="fw-bold text-success">🪙 {{ number_format($dispute->battle->prize) }} Points</span>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span class="text-secondary">Timestamp:</span>
                <span class="text-light">{{ $dispute->created_at->format('d M Y, h:i A') }}</span>
            </div>
        </div>

        <div class="mb-2">
            <label class="text-secondary small fw-bold">Complainant Statement:</label>
            <div class="p-2 rounded-2 text-light small" style="background: rgba(0,0,0,0.3); line-height: 1.4;">
                {{ $dispute->description }}
            </div>
        </div>

        {{-- Resolution Outcome (if resolved) --}}
        @if($dispute->status === 'resolved' || $dispute->status === 'rejected')
            <div class="mt-3 p-3 rounded-2" style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e;">
                <div class="fw-bold text-success small mb-1">
                    <i class="fa-solid fa-stamp me-1"></i> Official Administrator Verdict
                </div>
                <div class="text-white small mb-2" style="font-size: 12.5px;">
                    {{ $dispute->admin_decision_reason }}
                </div>
                <div class="text-secondary" style="font-size: 11px;">
                    Audited by: <strong class="text-light">{{ $dispute->resolver->name ?? 'Compliance Officer' }}</strong> on 
                    <strong>{{ $dispute->resolved_at ? $dispute->resolved_at->format('d M Y, h:i A') : '-' }}</strong>
                </div>
            </div>
        @elseif($dispute->status === 'more_evidence_requested')
            <div class="mt-3 p-3 rounded-2" style="background: rgba(234, 179, 8, 0.15); border: 1px solid #eab308;">
                <div class="fw-bold text-warning small mb-1">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Administrator Requested More Evidence
                </div>
                <div class="text-light small">
                    {{ $dispute->admin_decision_reason ?? 'Please provide uncropped match screenshots or game recording showing player handles.' }}
                </div>
            </div>
        @endif
    </div>

    {{-- Evidence Repository (Multi-party) --}}
    <div class="p-3 rounded-3 mb-3" style="background: #0b1735; border: 1px solid rgba(56, 189, 248, 0.25);">
        <h5 class="fw-bold text-white mb-2" style="font-size: 14.5px;">
            <i class="fa-solid fa-photo-film text-info me-1"></i> Match Evidence Dossier
        </h5>

        <div class="d-flex flex-column gap-2 mb-3">
            @forelse($dispute->evidences as $ev)
                <div class="p-2 rounded-2 d-flex align-items-center justify-content-between" style="background: #081226; border: 1px solid rgba(255,255,255,0.1);">
                    <div>
                        <div class="text-light small fw-bold" style="font-size: 12px;">
                            Uploaded by {{ $ev->user->name ?? 'Player' }}
                        </div>
                        <div class="text-secondary" style="font-size: 10.5px;">
                            {{ $ev->notes ?? 'Evidence attachment' }} &bull; {{ $ev->created_at->format('d M Y, h:i A') }}
                        </div>
                    </div>
                    <a href="{{ route('ludo.dispute.evidence.download', $ev->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-1 px-3" style="font-size: 11px;">
                        <i class="fa-solid fa-download me-1"></i> Download
                    </a>
                </div>
            @empty
                <div class="text-secondary small text-center py-2">No evidence uploaded yet.</div>
            @endforelse
        </div>

        {{-- Upload supplementary evidence form --}}
        @if($dispute->status !== 'resolved' && $dispute->status !== 'rejected')
            <div class="pt-2 border-top border-secondary border-opacity-25">
                <h6 class="text-white fw-bold mb-2" style="font-size: 13px;">Upload Supplementary Evidence</h6>
                <form action="{{ route('ludo.dispute.evidence', $dispute->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <textarea name="notes" rows="2" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" placeholder="Context notes for this screenshot..."></textarea>
                    </div>
                    <div class="mb-2">
                        <input type="file" name="evidence" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" accept=".jpg,.jpeg,.png,.webp,.mp4,.pdf" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-info rounded-pill py-1 px-3 fw-bold" style="font-size: 12px;">
                        <i class="fa-solid fa-upload me-1"></i> Upload Evidence File
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- Administrator Resolution Desk (Rendered if Admin) --}}
    @if(isset($isAdmin) && $isAdmin && $dispute->status !== 'resolved')
        <div class="p-3 rounded-3 mb-4" style="background: #141f3d; border: 2px solid #38bdf8;">
            <div class="d-flex align-items-center gap-2 mb-2 text-warning fw-bold small">
                <i class="fa-solid fa-shield-halved"></i> Administrator Decision Desk
            </div>

            <form action="{{ route('ludo.admin.dispute.resolve', $dispute->id) }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label text-light small fw-bold">Select Decision Action <span class="text-danger">*</span></label>
                    <select name="decision" class="form-select bg-dark text-light border-secondary border-opacity-50 small rounded-3" required>
                        <option value="award_creator">Award Victory to Creator ({{ $dispute->battle->creator->name }})</option>
                        <option value="award_joiner">Award Victory to Joiner ({{ $dispute->battle->joiner->name }})</option>
                        <option value="refund_both">Cancel & Refund Both Players (Mutual Draw/Crash)</option>
                        <option value="request_evidence">Request Additional Evidence</option>
                        <option value="reject_dispute">Reject Dispute (Dismiss Complaint)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-light small fw-bold">Mandatory Justification / Reason <span class="text-danger">*</span></label>
                    <textarea name="admin_reason" rows="3" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" placeholder="Explain the factual rationale based on match screenshots and audit logs..." required minlength="10"></textarea>
                    <div class="text-secondary" style="font-size: 10.5px; margin-top: 2px;">This written statement will be permanently recorded in the audit history.</div>
                </div>

                <button type="submit" class="btn btn-danger w-100 rounded-pill py-2 fw-bold" style="font-size: 13px;">
                    <i class="fa-solid fa-gavel me-1"></i> Execute Final Decision
                </button>
            </form>
        </div>
    @endif
@endsection
