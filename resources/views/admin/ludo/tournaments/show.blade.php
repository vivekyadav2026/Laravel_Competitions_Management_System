@extends('layouts.admin')

@section('title', 'Tournament Workbench — ' . $tournament->title)
@section('page_title', 'Tournament Workbench: ' . $tournament->title)

@section('content')
    <div class="mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <a href="{{ route('admin.ludo.tournaments.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            &larr; Back to Tournaments
        </a>
        <div class="d-flex align-items-center gap-2">
            @if(!in_array($tournament->status, ['completed', 'cancelled']))
                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#editTournamentModal">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit Tournament
                </button>
            @endif
            <span class="badge {{ $tournament->status === 'published' ? 'bg-success' : ($tournament->status === 'running' ? 'bg-primary' : ($tournament->status === 'completed' ? 'bg-warning text-dark' : ($tournament->status === 'cancelled' ? 'bg-danger' : 'bg-secondary'))) }} fs-6">
                {{ strtoupper($tournament->status) }}
            </span>
        </div>
    </div>

    {{-- Overview & Action Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-7">
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
                        <div class="text-light fw-bold small">{{ $tournament->starts_at ? $tournament->starts_at->format('d M Y, h:i A') : 'TBD' }}</div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-6">
                        <div class="text-secondary small">Registration Closes At</div>
                        <div class="text-warning small fw-semibold">
                            {{ $tournament->registration_closes_at ? $tournament->registration_closes_at->format('d M Y, h:i A') : 'Open until start' }}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-secondary small">Tournament Champion</div>
                        <div class="text-success small fw-bold">
                            {{ $tournament->winner ? $tournament->winner->name : 'Not Yet Crowned' }}
                        </div>
                    </div>
                </div>

                @if($tournament->description)
                    <div class="mt-3 p-2 rounded-2 text-light small" style="background: rgba(0,0,0,0.3);">
                        <strong>Description:</strong> {{ $tournament->description }}
                    </div>
                @endif

                @if($tournament->rules_text)
                    <div class="mt-2 p-2 rounded-2 text-secondary small" style="background: rgba(0,0,0,0.2);">
                        <strong>Tournament Rules:</strong> {{ $tournament->rules_text }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="admin-card h-100">
                <h6 class="text-white fw-bold mb-3 border-bottom border-secondary border-opacity-25 pb-2">
                    <i class="fa-solid fa-gear text-warning me-1"></i> Stage Transitions & Controls
                </h6>

                @if(!in_array($tournament->status, ['completed', 'cancelled']))
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
                                <option value="cancelled" {{ $tournament->status === 'cancelled' ? 'selected' : '' }}>Cancelled (Refund All Participants)</option>
                            </select>
                        </div>

                        @if($tournament->participants->count() > 0)
                            <div class="mb-2">
                                <label class="form-label text-secondary small fw-bold mb-1">Select Champion (When Marking Completed)</label>
                                <select name="winner_user_id" class="form-select form-select-sm bg-dark text-light border-secondary">
                                    <option value="">-- Optional: Select Winner for Prize Payout --</option>
                                    @foreach($tournament->participants as $part)
                                        <option value="{{ $part->user_id }}">{{ $part->user->name ?? 'Player #' . $part->user_id }} (Seed #{{ $part->seed ?? $loop->iteration }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label text-secondary small fw-bold mb-1">Reason / Administrative Note (Mandatory for Cancel)</label>
                            <input type="text" name="reason" class="form-control form-control-sm bg-dark text-light border-secondary" placeholder="Enter reason for status transition or cancellation...">
                        </div>

                        <button type="submit" class="btn btn-sm btn-info w-100 rounded-pill fw-bold" onclick="return confirm('Apply this tournament status transition?')">
                            Apply Status Transition
                        </button>
                    </form>

                    <div class="d-flex flex-wrap gap-2">
                        {{-- Close Registration Button --}}
                        <form action="{{ route('admin.ludo.tournaments.closeRegistration', $tournament->id) }}" method="POST" class="flex-grow-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                                <i class="fa-solid fa-lock me-1"></i> Close Registration
                            </button>
                        </form>

                        {{-- Generate Bracket Button --}}
                        <form action="{{ route('admin.ludo.tournaments.bracket', $tournament->id) }}" method="POST" class="flex-grow-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-warning w-100 rounded-pill" onclick="return confirm('Generate or overwrite tournament brackets with current registered players?')">
                                <i class="fa-solid fa-sitemap me-1"></i> Generate Bracket
                            </button>
                        </form>
                    </div>
                @else
                    <div class="alert alert-secondary bg-dark text-secondary small mb-0">
                        This tournament is <strong>{{ strtoupper($tournament->status) }}</strong> ({{ $tournament->completed_at ? $tournament->completed_at->format('d M Y, h:i A') : '' }}). Historical records and brackets are locked for audit integrity.
                    </div>
                @endif
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
                No elimination bracket generated yet. Once participants have registered, click "Generate Bracket" above.
            </div>
        @endif
    </div>

    {{-- Registered Participants List --}}
    <div class="admin-card mb-4">
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
                            <td class="fw-bold text-white">
                                <a href="{{ route('admin.ludo.users.show', $p->user_id) }}" class="text-white text-decoration-none">
                                    {{ $p->user->name ?? 'Player' }}
                                </a>
                            </td>
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

    {{-- Edit Tournament Modal --}}
    @if(!in_array($tournament->status, ['completed', 'cancelled']))
        <div class="modal fade" id="editTournamentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="background: #141f38; border: 1px solid #ffd700; color: #fff;">
                    <div class="modal-header border-secondary border-opacity-25">
                        <h5 class="modal-title fw-bold fs-6">
                            <i class="fa-solid fa-pen-to-square text-warning me-1"></i> Edit Tournament Configuration
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('admin.ludo.tournaments.update', $tournament->id) }}" method="POST">
                        @csrf
                        <div class="modal-body text-secondary small py-3">
                            <div class="mb-3">
                                <label class="form-label text-light fw-bold small">Tournament Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control bg-dark text-light border-secondary small" value="{{ $tournament->title }}" required maxlength="150">
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-4">
                                    <label class="form-label text-light fw-bold small">Entry Points <span class="text-danger">*</span></label>
                                    <input type="number" name="entry_fee" class="form-control bg-dark text-light border-secondary small" value="{{ $tournament->entry_fee }}" min="0" required {{ $tournament->participants->count() > 0 ? 'readonly' : '' }}>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label text-light fw-bold small">Prize Pool (Virtual Points) <span class="text-danger">*</span></label>
                                    <input type="number" name="prize_pool" class="form-control bg-dark text-light border-secondary small" value="{{ $tournament->prize_pool }}" min="50" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-light fw-bold small">Capacity (Max Players)</label>
                                    <select name="max_players" class="form-select bg-dark text-light border-secondary small">
                                        @foreach([4, 8, 16, 32, 64] as $cap)
                                            <option value="{{ $cap }}" {{ (int)$tournament->max_players === $cap ? 'selected' : '' }}>{{ $cap }} Players</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-light fw-bold small">Starts At <span class="text-danger">*</span></label>
                                    <input type="datetime-local" name="starts_at" class="form-control bg-dark text-light border-secondary small" value="{{ $tournament->starts_at ? $tournament->starts_at->format('Y-m-d\TH:i') : '' }}" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-light fw-bold small">Registration Closes At</label>
                                    <input type="datetime-local" name="registration_closes_at" class="form-control bg-dark text-light border-secondary small" value="{{ $tournament->registration_closes_at ? $tournament->registration_closes_at->format('Y-m-d\TH:i') : '' }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-light fw-bold small">Description</label>
                                <textarea name="description" rows="2" class="form-control bg-dark text-light border-secondary small">{{ $tournament->description }}</textarea>
                            </div>

                            <div class="mb-2">
                                <label class="form-label text-light fw-bold small">Specific Tournament Rules</label>
                                <textarea name="rules_text" rows="2" class="form-control bg-dark text-light border-secondary small">{{ $tournament->rules_text }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary border-opacity-25 pt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
