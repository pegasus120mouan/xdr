@extends('layouts.app')

@section('title', 'Agents - Athena XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Log Collection Agents</h1>
        <a href="{{ route('agents.create') }}" class="btn btn-primary">+ Deploy New Agent</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🖥️</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Total Agents</div>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon">🟢</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['active'] }}</div>
                <div class="stat-label">Active</div>
            </div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-icon">⏳</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['pending'] }}</div>
                <div class="stat-label">Pending</div>
            </div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-icon">⚫</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['inactive'] }}</div>
                <div class="stat-label">Inactive</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <select name="group" class="filter-select" onchange="this.form.submit()">
                <option value="">All Groups</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ request('group') == $group->id ? 'selected' : '' }}>
                        {{ $group->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
            @if(request()->hasAny(['group', 'status']))
                <a href="{{ route('agents.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <!-- Agents Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Agent</th>
                    <th>Hostname</th>
                    <th>IP Address</th>
                    <th>Group</th>
                    <th>OS</th>
                    <th>Last Heartbeat</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr>
                    <td>
                        <span class="status-indicator status-{{ $agent->status }}">
                            @switch($agent->status)
                                @case('active') 🟢 @break
                                @case('inactive') ⚫ @break
                                @case('pending') ⏳ @break
                                @case('error') 🔴 @break
                            @endswitch
                        </span>
                    </td>
                    <td>
                        <div class="agent-info">
                            <span class="agent-name">{{ $agent->name }}</span>
                            <span class="agent-id">{{ $agent->agent_id }}</span>
                        </div>
                    </td>
                    <td>{{ $agent->hostname }}</td>
                    <td><span class="ip-address">{{ $agent->ip_address ?? 'N/A' }}</span></td>
                    <td>
                        @if($agent->tenantGroup)
                            <span class="group-tag">{{ $agent->tenantGroup->name }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($agent->os_type)
                            <span class="os-badge">
                                @switch($agent->os_type)
                                    @case('linux') 🐧 @break
                                    @case('windows') 🪟 @break
                                    @case('macos') 🍎 @break
                                @endswitch
                                {{ ucfirst($agent->os_type) }}
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($agent->last_heartbeat)
                            <span class="time-ago {{ $agent->isOnline() ? 'online' : 'offline' }}">
                                {{ $agent->last_heartbeat->diffForHumans() }}
                            </span>
                        @else
                            <span class="never">Never</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="{{ route('agents.show', $agent) }}" class="btn btn-sm btn-icon" title="View">👁️</a>
                            <a href="{{ route('agents.logs', $agent) }}" class="btn btn-sm btn-icon" title="Logs">📋</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <div class="empty-icon">🖥️</div>
                        <div class="empty-text">No agents deployed yet</div>
                        <a href="{{ route('agents.create') }}" class="btn btn-primary">Deploy First Agent</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $agents->withQueryString()->links() }}
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-card.stat-success { border-left: 3px solid #22c55e; }
.stat-card.stat-warning { border-left: 3px solid #eab308; }
.stat-card.stat-danger { border-left: 3px solid #ef4444; }

.stat-icon { font-size: 2rem; }
.stat-value { font-size: 1.8rem; font-weight: 700; color: #fff; }
.stat-label { font-size: 0.8rem; color: #64748b; }

.filters-bar {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.filters-form {
    display: flex;
    gap: 12px;
    align-items: center;
}

.filter-select {
    padding: 8px 12px;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 0.85rem;
    min-width: 150px;
}

.table-container {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    overflow: hidden;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #151a24;
    padding: 12px 16px;
    text-align: left;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.data-table td {
    padding: 14px 16px;
    border-top: 1px solid #2d3748;
    font-size: 0.85rem;
}

.data-table tr:hover {
    background: rgba(0, 212, 255, 0.05);
}

.agent-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.agent-name {
    font-weight: 600;
    color: #fff;
}

.agent-id {
    font-size: 0.75rem;
    color: #64748b;
    font-family: monospace;
}

.ip-address {
    font-family: monospace;
    color: #00d4ff;
}

.group-tag {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.os-badge {
    font-size: 0.85rem;
}

.time-ago.online { color: #22c55e; }
.time-ago.offline { color: #64748b; }
.never { color: #64748b; font-style: italic; }

.status-indicator { font-size: 1rem; }

.action-buttons {
    display: flex;
    gap: 4px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary {
    background: linear-gradient(135deg, #0066cc 0%, #00d4ff 100%);
    color: #fff;
}

.btn-secondary {
    background: #374151;
    color: #e2e8f0;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(100, 116, 139, 0.2);
    border: none;
    border-radius: 6px;
}

.btn-icon:hover {
    background: rgba(0, 212, 255, 0.2);
}

.empty-state {
    text-align: center;
    padding: 60px 20px !important;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 12px;
}

.empty-text {
    color: #64748b;
    margin-bottom: 16px;
}

.pagination-container {
    margin-top: 20px;
    display: flex;
    justify-content: center;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: rgba(34, 197, 94, 0.2);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #22c55e;
}
</style>
@endsection
