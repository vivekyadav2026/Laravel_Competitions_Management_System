@extends('layouts.player')

@section('title', config('ludo.brand_name', 'Fsaludoclub') . ' — Tournament Arena')

@section('content')
    {{-- Header --}}
    <div class="ludo-history-header">
        <span class="ludo-history-line"></span>
        <h2 class="ludo-history-title mb-0">🏆 Tournament Arena</h2>
        <span class="ludo-history-line"></span>
    </div>

    {{-- Announcement / Hero Banner --}}
    <div class="card border-warning border-opacity-50 p-3 mb-4 rounded-4 shadow-sm"
         style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(15, 23, 42, 0.9)); border: 1px solid rgba(245, 158, 11, 0.4);">
        <div class="d-flex align-items-center gap-3">
            <div class="display-6">👑</div>
            <div>
                <h5 class="text-warning fw-bold mb-1">Official Bracket Tournaments</h5>
                <p class="text-white-50 small mb-0">
                    Compete in single-elimination multiplayer brackets for grand virtual prize pools! Strictly non-cash virtual gaming points.
                </p>
            </div>
        </div>
    </div>

    {{-- Tournaments List --}}
    <div class="d-flex flex-column gap-3 mb-4">
        @forelse($tournaments as $t)
            @php
                $isRegistered = in_array($t->id, $registeredTournamentIds);
                $isFull = $t->participants_count >= $t->max_players;
                $canJoin = ($t->status === 'published') && !$isFull && !$isRegistered;
            @endphp
            <div class="card bg-navy-card border-secondary border-opacity-50 p-3 rounded-4 shadow-sm">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-{{ $t->status === 'published' ? 'success' : ($t->status === 'running' ? 'warning text-dark' : 'secondary') }} rounded-pill text-uppercase px-2" style="font-size: 0.7rem;">
                                {{ $t->status }}
                            </span>
                            <span class="text-white-50 small font-monospace">#{{ $t->tournament_uid }}</span>
                            @if($isRegistered)
                                <span class="badge bg-info text-dark rounded-pill fw-bold" style="font-size: 0.7rem;">✓ REGISTERED</span>
                            @endif
                        </div>
                        <h5 class="text-white fw-bold mb-1">{{ $t->title }}</h5>
                        <p class="text-white-50 small mb-2">{{ Str::limit($t->description, 120) }}</p>

                        <div class="d-flex flex-wrap gap-3 text-white-50 small">
                            <div><span class="text-warning fw-bold">🪙 {{ number_format($t->entry_fee) }}</span> Entry</div>
                            <div><span class="text-success fw-bold">🏆 {{ number_format($t->prize_pool) }}</span> Prize Pool</div>
                            <div><span class="text-info fw-bold">{{ $t->participants_count }}/{{ $t->max_players }}</span> Players</div>
                            @if($t->starts_at)
                                <div>Starts: <span class="text-light">{{ $t->starts_at->format('d M, h:i A') }}</span></div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex flex-column align-items-md-end gap-2">
                        @if($isRegistered)
                            <button type="button" class="btn btn-outline-info rounded-pill px-4 py-2 fw-bold" disabled>
                                ✓ Enrolled
                            </button>
                        @elseif($canJoin)
                            <button type="button" class="btn btn-warning rounded-pill px-4 py-2 fw-bold text-dark shadow-sm"
                                    data-bs-toggle="modal" data-bs-target="#joinTournamentModal{{ $t->id }}">
                                Join Bracket &rarr;
                            </button>
                        @elseif($isFull)
                            <button type="button" class="btn btn-secondary rounded-pill px-4 py-2 fw-bold" disabled>
                                Bracket Full
                            </button>
                        @else
                            <button type="button" class="btn btn-secondary rounded-pill px-4 py-2 fw-bold" disabled>
                                Closed
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @if($canJoin)
                {{-- Join Confirmation Modal --}}
                <div class="modal fade" id="joinTournamentModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-white border-warning border-opacity-50 rounded-4">
                            <div class="modal-header border-secondary border-opacity-25">
                                <h5 class="modal-title fw-bold text-warning">Confirm Tournament Registration</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('ludo.tournaments.join', $t->id) }}" method="POST">
                                @csrf
                                <div class="modal-body">
                                    <p class="mb-3">You are about to register for <strong>{{ $t->title }}</strong>.</p>
                                    <ul class="list-unstyled bg-navy-card p-3 rounded-3 small text-white-50 mb-3">
                                        <li class="mb-1">Entry Fee: <strong class="text-warning">🪙 {{ number_format($t->entry_fee) }} Points</strong></li>
                                        <li class="mb-1">Grand Prize: <strong class="text-success">🪙 {{ number_format($t->prize_pool) }} Points</strong></li>
                                        <li>Bracket: Single Elimination ({{ $t->max_players }} Players)</li>
                                    </ul>
                                    <div class="alert alert-info py-2 small mb-0">
                                        ℹ️ Virtual points are deducted immediately and held securely. Points are non-cash and non-transferable.
                                    </div>
                                </div>
                                <div class="modal-footer border-secondary border-opacity-25">
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Confirm & Join</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="text-center py-5 bg-navy-card rounded-4 border border-secondary border-opacity-25">
                <div class="display-6 mb-2">🏆</div>
                <h5 class="text-white fw-bold mb-1">No Active Tournaments</h5>
                <p class="text-white-50 small mb-0">New tournament brackets will be announced soon. Stay tuned!</p>
            </div>
        @endforelse
    </div>

    @if($tournaments->hasPages())
        <div class="d-flex justify-content-center ludo-pagination-wrapper my-4">
            {{ $tournaments->links('pagination::bootstrap-5') }}
        </div>
    @endif
@endsection
