@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Support Ticket ' . $ticket->ticket_uid)

@section('content')
    <div class="mb-3">
        <a href="{{ route('ludo.support') }}" class="btn btn-sm btn-outline-secondary text-light rounded-pill py-1 px-3" style="font-size: 12px;">
            &larr; Back to Support Center
        </a>
    </div>

    {{-- Ticket Overview Card --}}
    <div class="p-3 rounded-3 mb-3" style="background: linear-gradient(165deg, #0f1c3f, #081226); border: 1.5px solid #2563eb;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-info fw-bold font-monospace fs-6">{{ $ticket->ticket_uid }}</span>
            <span class="badge {{ $ticket->status === 'open' ? 'bg-primary' : ($ticket->status === 'answered' ? 'bg-success' : ($ticket->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary')) }}" style="font-size: 11px; text-transform: uppercase;">
                {{ str_replace('_', ' ', $ticket->status) }}
            </span>
        </div>

        <h4 class="text-white fw-bold mb-2" style="font-size: 16px;">{{ $ticket->subject }}</h4>
        <div class="text-secondary small mb-3" style="font-size: 11.5px;">
            <span>Category: <strong class="text-info">{{ ucwords(str_replace('_', ' ', $ticket->category)) }}</strong></span> | 
            <span>Priority: <strong class="{{ $ticket->priority === 'urgent' ? 'text-danger' : 'text-light' }}">{{ ucfirst($ticket->priority) }}</strong></span> |
            <span>Opened: <strong>{{ $ticket->created_at->format('d M Y, h:i A') }}</strong></span>
        </div>

        <div class="p-3 rounded-2 text-light" style="background: rgba(0,0,0,0.3); font-size: 13px; line-height: 1.5;">
            {{ $ticket->description }}
        </div>

        @if($ticket->attachment_path)
            <div class="mt-3 pt-2 border-top border-secondary border-opacity-25">
                <span class="text-secondary small" style="font-size: 11px;">Attached Initial Evidence:</span>
                <a href="{{ route('ludo.ticket.attachment', $ticket->id) }}" class="btn btn-sm btn-outline-info rounded-pill ms-2 py-0 px-2" style="font-size: 11px;">
                    <i class="fa-solid fa-paperclip me-1"></i> Download File (Encrypted)
                </a>
            </div>
        @endif
    </div>

    {{-- Session alerts --}}
    @if(session('success'))
        <div class="alert alert-success bg-success bg-opacity-25 text-success border border-success border-opacity-50 small rounded-3 mb-3 py-2 px-3">
            <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Conversation Thread --}}
    <h5 class="text-white fw-bold mb-3" style="font-size: 14.5px;">
        <i class="fa-solid fa-comments text-info me-1"></i> Conversation History
    </h5>

    <div class="d-flex flex-column gap-3 mb-4">
        @forelse($ticket->messages as $msg)
            <div class="p-3 rounded-3 {{ $msg->is_admin ? 'ms-3 border-info' : 'me-3 border-secondary' }}" 
                 style="background: {{ $msg->is_admin ? '#0c234a' : '#081226' }}; border: 1px solid {{ $msg->is_admin ? '#38bdf8' : 'rgba(255,255,255,0.1)' }};">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold {{ $msg->is_admin ? 'text-warning' : 'text-info' }}" style="font-size: 12.5px;">
                        @if($msg->is_admin)
                            <i class="fa-solid fa-shield-halved me-1"></i> Support Admin
                        @else
                            <i class="fa-solid fa-user me-1"></i> {{ $msg->user->name ?? 'You' }}
                        @endif
                    </span>
                    <span class="text-secondary small" style="font-size: 10.5px;">{{ $msg->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="text-light" style="font-size: 13px; line-height: 1.4;">
                    {{ $msg->message }}
                </div>
                @if($msg->attachment_path)
                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-25">
                        <span class="badge bg-secondary" style="font-size: 10px;">
                            <i class="fa-solid fa-file me-1"></i> Evidence Attached
                        </span>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-3 rounded-3 text-center text-secondary small" style="background: #081226;">
                No replies yet. An administrator will respond shortly.
            </div>
        @endforelse
    </div>

    {{-- Post Reply Card --}}
    @if($ticket->status !== 'closed')
        <div class="p-3 rounded-3 mb-4" style="background: #0b1735; border: 1px solid rgba(56, 189, 248, 0.3);">
            <h6 class="text-white fw-bold mb-2" style="font-size: 13.5px;">Post a Reply</h6>
            <form action="{{ route('ludo.ticket.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-2">
                    <textarea name="message" rows="3" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" placeholder="Type your response or update..." required minlength="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label text-light small mb-1" style="font-size: 11px;">Attach Additional Evidence (Optional, max 5MB):</label>
                    <input type="file" name="attachment" class="form-control bg-dark text-light border-secondary border-opacity-50 small rounded-3" accept=".jpg,.jpeg,.png,.webp,.pdf">
                </div>
                <button type="submit" class="ludo-btn-invite w-100" style="padding: 9px; font-size: 13px;">
                    <i class="fa-solid fa-reply me-1"></i> Send Reply
                </button>
            </form>
        </div>
    @else
        <div class="alert alert-secondary bg-dark text-secondary text-center small rounded-3 py-2 mb-4">
            This support ticket has been closed. Please create a new ticket if you have another query.
        </div>
    @endif
@endsection
