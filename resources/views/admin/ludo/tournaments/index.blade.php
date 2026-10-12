@extends('layouts.admin')

@section('title', 'Tournaments & Brackets — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Tournaments & Brackets')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="text-white fw-bold mb-0">Tournaments Management</h5>
        <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createTournamentModal">
            <i class="fa-solid fa-plus me-1"></i> Create Tournament
        </button>
    </div>

    {{-- Tournaments Table Card --}}
    <div class="admin-card mb-4">
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Tournament Title</th>
                        <th>Entry / Prize Pool</th>
                        <th>Capacity</th>
                        <th>Status</th>
                        <th>Starts At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tournaments as $t)
                        <tr>
                            <td class="font-monospace text-info small">{{ $t->tournament_uid }}</td>
                            <td>
                                <div class="fw-bold text-white">{{ $t->title }}</div>
                                <span class="text-secondary small">Created by {{ $t->creator->name ?? 'Admin' }}</span>
                            </td>
                            <td>
                                <span class="text-light">🪙 {{ number_format($t->entry_fee) }}</span> / 
                                <strong class="text-success">🪙 {{ number_format($t->prize_pool) }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-info">
                                    {{ $t->participants_count }} / {{ $t->max_players }} Players
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $t->status === 'published' ? 'bg-success' : ($t->status === 'running' ? 'bg-primary' : ($t->status === 'completed' ? 'bg-warning text-dark' : 'bg-secondary')) }}">
                                    {{ strtoupper($t->status) }}
                                </span>
                            </td>
                            <td class="text-secondary small">{{ $t->starts_at ? $t->starts_at->format('d M Y, h:i A') : 'TBD' }}</td>
                            <td>
                                <a href="{{ route('admin.ludo.tournaments.show', $t->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                    Workbench &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No tournaments created yet. Click "Create Tournament" to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $tournaments->links('pagination::bootstrap-5') }}</div>
    </div>

    {{-- Create Tournament Modal --}}
    <div class="modal fade" id="createTournamentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #141f38; border: 1px solid #38bdf8; color: #fff;">
                <div class="modal-header border-secondary border-opacity-25">
                    <h5 class="modal-title fw-bold fs-6">
                        <i class="fa-solid fa-trophy text-warning me-1"></i> New Tournament Contest
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.ludo.tournaments.store') }}" method="POST">
                    @csrf
                    <div class="modal-body text-secondary small py-3">
                        <div class="mb-2">
                            <label class="form-label text-light fw-bold small">Tournament Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control bg-dark text-light border-secondary small" placeholder="e.g. Fsaludoclub Grand Masters Championship" required maxlength="150">
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-light fw-bold small">Entry Points <span class="text-danger">*</span></label>
                                <input type="number" name="entry_fee" class="form-control bg-dark text-light border-secondary small" value="100" min="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-light fw-bold small">Prize Pool (Points) <span class="text-danger">*</span></label>
                                <input type="number" name="prize_pool" class="form-control bg-dark text-light border-secondary small" value="1500" min="50" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-light fw-bold small">Capacity (Bracket Size)</label>
                                <select name="max_players" class="form-select bg-dark text-light border-secondary small">
                                    <option value="4">4 Players (Semi-Finals)</option>
                                    <option value="8">8 Players (Quarter-Finals)</option>
                                    <option value="16" selected>16 Players (Round of 16)</option>
                                    <option value="32">32 Players (Round of 32)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-light fw-bold small">Start Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="starts_at" class="form-control bg-dark text-light border-secondary small" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-light fw-bold small">Tournament Description & Rules</label>
                            <textarea name="description" rows="2" class="form-control bg-dark text-light border-secondary small" placeholder="Describe format, rules, and timetable..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary border-opacity-25 pt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold">Create Tournament</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
