@extends('layouts.admin')

@section('title', 'Tournament Workbench — ' . $tournament->title)
@section('page_title', 'Tournament Workbench')

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.ludo.tournaments.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Back to Tournaments
        </a>
        <div class="d-flex gap-2">
            <span class="badge {{ $tournament->status === 'published' ? 'bg-success' : ($tournament->status === 'running' ? 'bg-primary' : ($tournament->status === 'completed' ? 'bg-warning text-dark' : 'bg-secondary')) }} fs-6">
                {{ strtoupper($tournament->status) }}
            </span>
        </div>
    </div>

    {{-- Overview & Action Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <div class="admin-card h-100">
                <h5 class="text-white fw-bold mb-1">{{ $tournament->title }}</h5>
                <span class="text-secondary small font-monospace">UID: {{ $tournament->tournament_uid }}</span>

                <div class="row g-3 mt-2 pt-2 border-top border-secondary border-opacity-25">
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Entry Points</div>
                        <div class="text-white fw-bold">🪙 {{ number_format($tournament->entry_fee) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Prize Pool</div>
                        <div class="text-success fw-bold">🪙 {{ number_format($tournament->prize_pool) }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Participants</div>
                        <div class="text-info fw-bold">{{ $tournament->participants->count() }} / {{ $tournament->max_players }}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-secondary small">Starts At</div>
                        <div class="text-light fw-bold">{{ $tournament->starts_at ? $tournament->starts_at->format('d M, h:i A') : 'TBD' }}</div>
                    </div>
                </div>

                @if($tournament->description)
                    <div class="mt-3 p-2 rounded-2 text-light small" style="background: rgba(0,0,0,0.3);">
                        {{ $tournament->description }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-gear text-warning me-1"></i> Stage Transitions
                </h6>

                {{-- Status Change Form --}}
                <form action="{{ route('admin.ludo.tournaments.status', $tournament->id) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label text-secondary small fw-bold mb-1">Update Tournament Status</label>
                        <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                            <option value="draft" {{ $tournament->status === 'draft' ? 'selected' : '' }}>Draft (Private)</option>
                            <option value="published" {{ $tournament->status === 'published' ? 'selected' : '' }}>Published (Registration Open)</option>
                            <option value="running" {{ $tournament->status === 'running' ? 'selected' : '' }}>Running (Live Matches)</option>
                            <option value="completed" {{ $tournament->status === 'completed' ? 'selected' : '' }}>Completed (Finalized)</option>
                            <option value="cancelled" {{ $tournament->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-info w-100 rounded-pill fw-bold">
                        Apply Status Transition
                    </button>
                </form>

                {{-- Generate Bracket Button --}}
                <form action="{{ route('admin.ludo.tournaments.bracket', $tournament->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-warning w-100 rounded-pill" onclick="return confirm('Generate or overwrite tournament brackets with current registered players?')">
                        <i class="fa-solid fa-sitemap me-1"></i> Generate Elimination Bracket
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Bracket Visualizer Panel --}}
    <div class="admin-card mb-4">
        <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-diagram-project text-info me-1"></i> Single Elimination Bracket
        </h6>

        @if(!empty($tournament->bracket_data) && isset($tournament->bracket_data['round_1']))
            <div class="row g-3">
                @foreach($tournament->bracket_data['round_1'] as $match)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-3 rounded-3" style="background: #0b1329; border: 1.5px solid #2563eb;">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom border-secondary border-opacity-25">
                                <span class="badge bg-primary" style="font-size: 9.5px;">Match #{{ $match['match_index'] }}</span>
                                <span class="badge bg-dark text-info border border-secondary" style="font-size: 9.5px;">Round 1</span>
                            </div>

                            <div class="d-flex flex-column gap-2">
                                <div class="p-2 rounded-2 d-flex align-items-center justify-content-between" style="background: rgba(255,255,255,0.05);">
                                    <span class="text-white fw-bold small">
                                        <i class="fa-solid fa-user text-info me-1"></i> {{ $match['player1']['name'] ?? 'BYE' }}
                                    </span>
                                    <span class="badge bg-secondary" style="font-size: 9px;">Seed {{ $match['player1']['seed'] ?? '-' }}</span>
                                </div>
                                <div class="text-center text-secondary small fw-bold" style="font-size: 10px;">VS</div>
                                <div class="p-2 rounded-2 d-flex align-items-center justify-content-between" style="background: rgba(255,255,255,0.05);">
                                    <span class="text-white fw-bold small">
                                        <i class="fa-solid fa-user text-warning me-1"></i> {{ $match['player2']['name'] ?? 'BYE' }}
                                    </span>
                                    <span class="badge bg-secondary" style="font-size: 9px;">Seed {{ $match['player2']['seed'] ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4 text-secondary small">
                <i class="fa-solid fa-sitemap fs-2 text-secondary opacity-50 mb-2 d-block"></i>
                No elimination bracket generated yet. Once participants have registered, click "Generate Elimination Bracket" above.
            </div>
        @endif
    </div>

    {{-- Registered Participants List --}}
    <div class="admin-card">
        <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
            <i class="fa-solid fa-users text-primary me-1"></i> Registered Participants ({{ $tournament->participants->count() }})
        </h6>

        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Seed</th>
                        <th>Player Name</th>
                        <th>Email</th>
                        <th>Current Round</th>
                        <th>Status</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tournament->participants as $p)
                        <tr>
                            <td><span class="badge bg-dark border border-secondary text-info">#{{ $p->seed ?? $loop->iteration }}</span></td>
                            <td class="fw-bold text-white">{{ $p->user->name ?? 'Player' }}</td>
                            <td class="text-secondary small">{{ $p->user->email ?? 'N/A' }}</td>
                            <td>Round {{ $p->current_round }}</td>
                            <td><span class="badge {{ $p->status === 'winner' ? 'bg-success' : ($p->status === 'eliminated' ? 'bg-danger' : 'bg-primary') }}">{{ strtoupper($p->status) }}</span></td>
                            <td class="text-secondary small">{{ $p->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-3">No players registered yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
