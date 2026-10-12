@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Support Center')

@section('content')
    {{-- Support Header --}}
    <div class="ludo-support-header text-center my-3">
        <h2 class="ludo-support-title fw-bold" style="font-size: 22px; letter-spacing: 0.5px;">
            <span style="color: #ffffff;">SUPPORT</span> <span style="background: linear-gradient(135deg, #38bdf8, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">CENTER</span>
        </h2>
        <div class="ludo-support-subtitle text-secondary small mt-1" style="font-size: 13px;">
            {{ $supportConfig['subtitle'] ?? 'We are available to assist you with matches, balance, and account queries' }}
        </div>
        <div class="mt-1">
            <span class="badge bg-dark text-info border border-info border-opacity-25" style="font-size: 11px;">
                <i class="fa-solid fa-clock me-1"></i> {{ $supportConfig['availability_notice'] ?? 'Standard Desk: Mon-Sun 10:00 AM - 10:00 PM IST' }}
            </span>
        </div>
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="alert alert-success bg-success bg-opacity-25 text-success border border-success border-opacity-50 small rounded-3 mb-3 py-2 px-3">
            <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 small rounded-3 mb-3 py-2 px-3">
            <i class="fa-solid fa-circle-exclamation me-1"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Official Channels Cards List (Matching reference design) --}}
    <div class="ludo-support-list d-flex flex-column gap-2 mb-4">
        {{-- Card 1: Our Support Team --}}
        <a href="{{ $supportConfig['team_whatsapp_url'] ?? '#' }}" 
           target="_blank" 
           class="ludo-support-item text-decoration-none d-flex align-items-center justify-content-between p-3 rounded-3"
           style="background: linear-gradient(145deg, #0f1c3f, #081226); border: 1px solid rgba(56, 189, 248, 0.25); box-shadow: 0 4px 14px rgba(0,0,0,0.4);">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 44px; height: 44px; background: rgba(56, 189, 248, 0.15); border: 1.5px solid #38bdf8;">
                    <i class="fa-solid fa-headset text-info fs-5"></i>
                </div>
                <div>
                    <div class="text-white fw-bold" style="font-size: 14.5px;">Our Support Team</div>
                    <div class="text-success small fw-semibold" style="font-size: 11.5px;">
                        <span class="d-inline-block rounded-circle bg-success me-1" style="width: 6px; height: 6px;"></span>
                        Verified Direct Desk
                    </div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-info"></i>
        </a>

        {{-- Card 2: Telegram Support --}}
        <a href="{{ $supportConfig['telegram_url'] ?? '#' }}" 
           target="_blank" 
           class="ludo-support-item text-decoration-none d-flex align-items-center justify-content-between p-3 rounded-3"
           style="background: linear-gradient(145deg, #0f1c3f, #081226); border: 1px solid rgba(129, 140, 248, 0.25); box-shadow: 0 4px 14px rgba(0,0,0,0.4);">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 44px; height: 44px; background: rgba(59, 130, 246, 0.15); border: 1.5px solid #60a5fa;">
                    <i class="fa-brands fa-telegram text-primary fs-5"></i>
                </div>
                <div>
                    <div class="text-white fw-bold" style="font-size: 14.5px;">Telegram Support</div>
                    <div class="text-secondary small" style="font-size: 11.5px;">Official Announcement & Help Desk</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-info"></i>
        </a>

        {{-- Card 3: Contact Us / Email & Ticket Helpdesk --}}
        <a href="{{ $supportConfig['contact_url'] ?? '#' }}" 
           class="ludo-support-item text-decoration-none d-flex align-items-center justify-content-between p-3 rounded-3"
           style="background: linear-gradient(145deg, #0f1c3f, #081226); border: 1px solid rgba(34, 197, 94, 0.25); box-shadow: 0 4px 14px rgba(0,0,0,0.4);">
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 44px; height: 44px; background: rgba(34, 197, 94, 0.15); border: 1.5px solid #4ade80;">
                    <i class="fa-solid fa-envelope text-success fs-5"></i>
                </div>
                <div>
                    <div class="text-white fw-bold" style="font-size: 14.5px;">Contact Us</div>
                    <div class="text-secondary small" style="font-size: 11.5px;">Administrative Inquiries</div>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-info"></i>
        </a>
    </div>

    {{-- Verified Security Tag --}}
    <div class="text-center py-2 mb-4 rounded-pill border border-info border-opacity-25" style="background: rgba(8, 18, 38, 0.8);">
        <span class="small fw-bold text-info" style="font-size: 11.5px; letter-spacing: 0.5px;">
            <i class="fa-solid fa-shield-halved me-1"></i> SECURE & VERIFIED SUPPORT
        </span>
    </div>

    {{-- Support Tickets Section --}}
    <div class="ludo-create-battle-card mb-4" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #2563eb; border-radius: 14px; padding: 18px;">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-ticket-simple text-warning fs-5"></i>
                <h5 class="fw-bold text-white mb-0" style="font-size: 15px;">Open a Support Ticket</h5>
            </div>
            <span class="badge bg-primary text-white" style="font-size: 10px;">Audit Logged</span>
        </div>

        <form action="{{ route('ludo.ticket.create') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row g-2 mb-2">
                <div class="col-12 col-md-6">
                    <label class="form-label text-light small fw-bold mb-1">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select bg-dark text-light border-secondary border-opacity-50 small rounded-3" required>
                        <option value="battle_issue">Battle Issue (Opponent / Room Code)</option>
                        <option value="deposit_wallet">Virtual Wallet & Ledger</option>
                        <option value="kyc_issue">KYC Identity Verification</option>
                        <option value="account_profile">Profile & Security</option>
                        <option value="technical_bug">Technical Bug / Glitch</option>
                        <option value="other">Other Inquiry</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label text-light small fw-bold mb-1">Priority</label>
                    <select name="priority" class="form-select bg-dark text-light border-secondary border-opacity-50 small rounded-3">
                        <option value="normal">Normal</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <div class="mb-2">
                <label class="form-label text-light small fw-bold mb-1">Subject <span class="text-danger">*</span></label>
                <input type="text" name="subject" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" placeholder="Brief summary of your inquiry" required maxlength="190">
            </div>

            <div class="mb-2">
                <label class="form-label text-light small fw-bold mb-1">Detailed Description <span class="text-danger">*</span></label>
                <textarea name="description" rows="3" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" placeholder="Provide complete match ID, transaction reference, or step-by-step description..." required minlength="10"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label text-light small fw-bold mb-1">Attach Evidence / Screenshot (Optional, max 5MB)</label>
                <input type="file" name="attachment" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <div class="text-secondary" style="font-size: 10px; margin-top: 2px;">Stored in encrypted private storage; accessible only by you and administrators.</div>
            </div>

            <button type="submit" class="ludo-btn-invite w-100" style="padding: 11px; font-size: 13.5px; font-weight: bold;">
                <i class="fa-solid fa-paper-plane me-1"></i> Submit Support Ticket
            </button>
        </form>
    </div>

    {{-- My Tickets List --}}
    <div class="mb-4">
        <h5 class="fw-bold text-white mb-2" style="font-size: 14.5px;">
            <i class="fa-solid fa-list-check text-info me-1"></i> Your Support Tickets
        </h5>

        @if(isset($tickets) && $tickets->count() > 0)
            <div class="d-flex flex-column gap-2">
                @foreach($tickets as $t)
                    <div class="p-3 rounded-3" style="background: #0b1735; border: 1px solid rgba(56, 189, 248, 0.2);">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="text-info fw-bold font-monospace" style="font-size: 12px;">{{ $t->ticket_uid }}</span>
                            <span class="badge {{ $t->status === 'open' ? 'bg-primary' : ($t->status === 'answered' ? 'bg-success' : ($t->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary')) }}" style="font-size: 9.5px; text-transform: uppercase;">
                                {{ str_replace('_', ' ', $t->status) }}
                            </span>
                        </div>
                        <div class="text-white fw-semibold mb-1" style="font-size: 13px;">{{ $t->subject }}</div>
                        <div class="d-flex align-items-center justify-content-between text-secondary small" style="font-size: 11px;">
                            <span>Category: <strong class="text-light">{{ ucwords(str_replace('_', ' ', $t->category)) }}</strong></span>
                            <span>{{ $t->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-end">
                            <a href="{{ route('ludo.ticket.view', $t->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-1 px-3" style="font-size: 11.5px;">
                                View Ticket & Conversation &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-2">
                {{ $tickets->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="p-3 rounded-3 text-center text-secondary small" style="background: #0b1735; border: 1px dashed rgba(255,255,255,0.15);">
                <i class="fa-solid fa-inbox fs-4 mb-2 d-block text-secondary opacity-50"></i>
                You have no active support tickets.
            </div>
        @endif
    </div>
@endsection
