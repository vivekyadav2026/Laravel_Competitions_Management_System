@extends('layouts.admin')

@section('title', 'User Management — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'User Management')

@section('content')
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.users.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search by name, email, UID, phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="role" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Roles</option>
                    <option value="normal" {{ request('role') === 'normal' ? 'selected' : '' }}>Player (Normal)</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="superadmin" {{ request('role') === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                    <option value="support_staff" {{ request('role') === 'support_staff' ? 'selected' : '' }}>Support Staff</option>
                    <option value="moderator" {{ request('role') === 'moderator' ? 'selected' : '' }}>Moderator</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="kyc_status" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All KYC</option>
                    <option value="verified" {{ request('kyc_status') === 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="pending" {{ request('kyc_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="unverified" {{ request('kyc_status') === 'unverified' ? 'selected' : '' }}>Unverified</option>
                    <option value="rejected" {{ request('kyc_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.users.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
            </div>
        </form>

        {{-- Users Table --}}
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>UID</th>
                        <th>Player Name</th>
                        <th>Email & Phone</th>
                        <th>Points Balance</th>
                        <th>KYC</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td class="font-monospace text-info small">{{ $u->player_uid ?? 'LUDO' . str_pad($u->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="fw-bold text-white">{{ $u->name }}</div>
                                <span class="text-secondary" style="font-size: 11px;">Joined {{ $u->created_at->format('d M Y') }}</span>
                            </td>
                            <td>
                                <div class="text-light small">{{ $u->email }}</div>
                                <div class="text-secondary small">{{ $u->phone ?? 'No phone' }}</div>
                            </td>
                            <td>
                                <span class="fw-bold text-warning">🪙 {{ number_format($u->coins) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $u->kyc_status === 'verified' ? 'bg-success' : ($u->kyc_status === 'pending' ? 'bg-warning text-dark' : 'bg-secondary') }}" style="font-size: 9.5px; text-transform: uppercase;">
                                    {{ $u->kyc_status }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-info" style="font-size: 10px;">
                                    {{ strtoupper($u->role) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $u->status === 'active' ? 'bg-success' : 'bg-danger' }}" style="font-size: 10px; text-transform: uppercase;">
                                    {{ $u->status }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <a href="{{ route('admin.ludo.users.show', $u->id) }}" class="btn btn-sm btn-outline-info rounded-pill py-0 px-2" style="font-size: 11px;">
                                        Dossier
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">No registered players matching your query.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-3">
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
