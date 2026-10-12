@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Notifications')

@section('content')
    {{-- Header --}}
    <div class="ludo-history-header">
        <span class="ludo-history-line"></span>
        <h2 class="ludo-history-title mb-0">🔔 Notifications</h2>
        <span class="ludo-history-line"></span>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="text-white-50 small">
            Stay updated with your matches, tournaments, wallet, and support tickets.
        </div>
        @if(auth()->user()->unreadNotifications->count() > 0)
            <form action="{{ route('ludo.notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1 small fw-bold">
                    ✓ Mark all read
                </button>
            </form>
        @endif
    </div>

    {{-- Notification List --}}
    <div class="d-flex flex-column gap-3 mb-4">
        @forelse($notifications as $notif)
            @php
                $isUnread = is_null($notif->read_at);
                $data = $notif->data;
                $title = $data['title'] ?? 'Notification';
                $message = $data['message'] ?? '';
                $actionUrl = $data['action_url'] ?? null;
                $type = $data['type'] ?? 'system';
            @endphp
            <div class="card bg-navy-card border-{{ $isUnread ? 'info' : 'secondary border-opacity-25' }} p-3 rounded-4 shadow-sm position-relative">
                @if($isUnread)
                    <span class="position-absolute top-0 end-0 translate-middle p-2 bg-info border border-light rounded-circle" style="transform: translate(-12px, 12px) !important;">
                        <span class="visually-hidden">New notification</span>
                    </span>
                @endif
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 44px; height: 44px; background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3);">
                        @if($type === 'tournament')
                            🏆
                        @elseif($type === 'match')
                            ⚔️
                        @elseif($type === 'dispute')
                            ⚖️
                        @elseif($type === 'support')
                            🎫
                        @elseif($type === 'ledger')
                            🪙
                        @else
                            🔔
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="mb-0 fw-bold {{ $isUnread ? 'text-white' : 'text-white-50' }}">{{ $title }}</h6>
                            <span class="text-white-50 small" style="font-size: 0.75rem;">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-light mb-2 small" style="line-height: 1.4;">{{ $message }}</p>
                        
                        <div class="d-flex align-items-center gap-2">
                            @if($actionUrl)
                                <a href="{{ $actionUrl }}" class="btn btn-sm btn-info rounded-pill px-3 py-1 fw-bold small text-dark">
                                    View Details &rarr;
                                </a>
                            @endif

                            @if($isUnread)
                                <form action="{{ route('ludo.notifications.read', $notif->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 small text-white-50" style="font-size: 0.75rem;">
                                        Mark as read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-navy-card rounded-4 border border-secondary border-opacity-25">
                <div class="display-6 mb-2">🔕</div>
                <h5 class="text-white fw-bold mb-1">No Notifications</h5>
                <p class="text-white-50 small mb-0">You have no new alerts at the moment.</p>
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="d-flex justify-content-center">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
