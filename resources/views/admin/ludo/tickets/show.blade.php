@extends('layouts.admin')

@section('title', 'Support Ticket — ' . $ticket->ticket_uid)
@section('page_title', 'Support Helpdesk Ticket: ' . $ticket->ticket_uid)

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.ludo.tickets.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Back to Support Desk
        </a>
        <div class="d-flex gap-2">
            <span class="badge {{ $ticket->priority === 'urgent' ? 'bg-danger' : 'bg-secondary' }} fs-6 text-uppercase">
                {{ $ticket->priority }} Priority
            </span>
            <span class="badge {{ $ticket->status === 'open' ? 'bg-primary' : ($ticket->status === 'answered' ? 'bg-success' : 'bg-warning text-dark') }} fs-6 text-uppercase">
                {{ str_replace('_', ' ', $ticket->status) }}
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Ticket Info & Conversation --}}
        <div class="col-12 col-lg-8">
            <div class="admin-card mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2 pb-2 border-bottom border-secondary border-opacity-25">
                    <div>
                        <h5 class="text-white fw-bold mb-1">{{ $ticket->subject }}</h5>
                        <div class="text-secondary small">
                            Category: <strong class="text-info">{{ ucwords(str_replace('_', ' ', $ticket->category)) }}</strong> &bull;
                            Player: <a href="{{ route('admin.ludo.users.show', $ticket->user_id) }}" class="text-warning text-decoration-none fw-bold">{{ $ticket->user->name ?? 'Player' }}</a> ({{ $ticket->user->email ?? '' }})
                        </div>
                    </div>
                    @if($ticket->attachment_path)
                        <a href="{{ route('ludo.ticket.attachment', $ticket->id) }}" class="btn btn-sm btn-outline-info rounded-pill px-3">
                            <i class="fa-solid fa-paperclip me-1"></i> Download Attachment
                        </a>
                    @endif
                </div>
                <div class="p-3 rounded-3 text-light small" style="background: rgba(0,0,0,0.3);">
                    {{ $ticket->description }}
                </div>
            </div>

            {{-- Conversation Thread --}}
            <div class="admin-card mb-3">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-comments text-info me-1"></i> Conversation Thread ({{ $ticket->messages->count() }})
                </h6>

                <div class="d-flex flex-column gap-3 mb-4">
                    @forelse($ticket->messages as $msg)
                        <div class="p-3 rounded-3" style="background: {{ $msg->is_admin ? 'rgba(212, 175, 55, 0.08)' : 'rgba(56, 189, 248, 0.08)' }}; border: 1px solid {{ $msg->is_admin ? 'rgba(212, 175, 55, 0.3)' : 'rgba(56, 189, 248, 0.25)' }};">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small {{ $msg->is_admin ? 'text-warning' : 'text-info' }}">
                                    <i class="fa-solid {{ $msg->is_admin ? 'fa-user-shield' : 'fa-user' }} me-1"></i>
                                    {{ $msg->user->name ?? 'User' }} {{ $msg->is_admin ? '(Support Staff)' : '(Player)' }}
                                </span>
                                <span class="text-secondary" style="font-size: 10.5px;">{{ $msg->created_at->format('d M Y, h:i A') }}</span>
                            </div>
                            <div class="text-light small">{{ $msg->message }}</div>
                        </div>
                    @empty
                        <div class="text-center text-secondary py-3 small">No replies posted yet. Send a response below.</div>
                    @endforelse
                </div>

                {{-- Staff Reply Form --}}
                <form action="{{ route('ludo.ticket.reply', $ticket->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label text-light small fw-bold">Post Official Support Reply <span class="text-danger">*</span></label>
                        <textarea name="message" rows="3" class="form-control bg-dark text-light border-secondary small" placeholder="Type your response to the player (player will receive an in-app notification)..." required minlength="2"></textarea>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
                        <input type="file" name="attachment" class="form-control form-control-sm bg-dark text-secondary border-secondary" style="max-width: 280px;">
                        <button type="submit" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-paper-plane me-1"></i> Send Reply to Player
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Status & Priority Governance Controls --}}
        <div class="col-12 col-lg-4">
            <div class="admin-card">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-sliders text-warning me-1"></i> Ticket Status & Priority
                </h6>

                <form action="{{ route('admin.ludo.tickets.status', $ticket->id) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold mb-1">Ticket Status</label>
                        <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open (Awaiting Staff)</option>
                            <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="answered" {{ $ticket->status === 'answered' ? 'selected' : '' }}>Answered</option>
                            <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold mb-1">Priority Level</label>
                        <select name="priority" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="normal" {{ $ticket->priority === 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-sm btn-info w-100 rounded-pill fw-bold">
                        Update Ticket State
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
