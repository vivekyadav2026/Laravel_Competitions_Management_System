@extends('layouts.admin')

@section('title', 'Administrative Audit Logs — ' . config('ludo.brand_name', 'Fsaludoclub'))
@section('page_title', 'Administrative Audit Log Explorer')

@section('content')
    <div class="admin-card mb-4">
        {{-- Search & Filter Bar --}}
        <form action="{{ route('admin.ludo.audit.index') }}" method="GET" class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-4 col-xl-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-dark text-secondary border-secondary"><i class="fa-solid fa-search"></i></span>
                    <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search description, action, admin, IP..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <select name="action_type" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Action Types</option>
                    @foreach($actionTypes ?? [] as $act)
                        <option value="{{ $act }}" {{ request('action_type') === $act ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $act)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-6 col-md-2">
                <select name="target_type" class="form-select form-select-sm bg-dark text-light border-secondary">
                    <option value="">All Resource Types</option>
                    @foreach($targetTypes ?? [] as $tgt)
                        <option value="{{ $tgt }}" {{ request('target_type') === $tgt ? 'selected' : '' }}>{{ $tgt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-3 col-md-2">
                <input type="date" name="from" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('from') }}" title="From Date">
            </div>
            <div class="col-6 col-sm-3 col-md-1">
                <input type="date" name="to" class="form-control form-control-sm bg-dark text-light border-secondary" value="{{ request('to') }}" title="To Date">
            </div>
            <div class="col-12 col-sm-6 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-info rounded-pill px-3 fw-bold flex-grow-1">Filter</button>
                <a href="{{ route('admin.ludo.audit.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2">Reset</a>
            </div>
        </form>

        {{-- Audit Logs Table --}}
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Administrator</th>
                        <th>Action Type</th>
                        <th>Affected Resource</th>
                        <th>Audit Description & Reason</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="font-monospace text-secondary small">#{{ $log->id }}</td>
                            <td>
                                <div class="text-white small fw-bold">{{ $log->admin->name ?? 'Admin #' . $log->admin_id }}</div>
                                <span class="text-secondary small">{{ $log->admin->email ?? '' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-info" style="font-size: 10px; text-transform: uppercase;">
                                    {{ str_replace('_', ' ', $log->action_type) }}
                                </span>
                            </td>
                            <td class="font-monospace small text-warning">
                                {{ $log->target_type ? $log->target_type . ' #' . $log->target_id : 'System' }}
                            </td>
                            <td class="small text-light" style="max-width: 360px;">
                                {{ $log->description }}
                            </td>
                            <td class="font-monospace text-secondary small">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                            <td class="text-secondary small">
                                {{ $log->created_at->format('d M Y, h:i:s A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">No administrative audit log records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $logs->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
