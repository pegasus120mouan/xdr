@extends('layouts.app')

@section('title', 'Security Alerts - Athena XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Security Alerts</h1>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🔔</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['total'] }}</div>
                <div class="stat-label">Total Alerts</div>
            </div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-icon">🚨</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['new'] }}</div>
                <div class="stat-label">New Alerts</div>
            </div>
        </div>
        <div class="stat-card stat-critical">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['critical'] }}</div>
                <div class="stat-label">Critical Pending</div>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['resolved_today'] }}</div>
                <div class="stat-label">Resolved Today</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                @foreach($statuses as $key => $status)
                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                        {{ $status['label'] }}
                    </option>
                @endforeach
            </select>
            <select name="severity" class="filter-select" onchange="this.form.submit()">
                <option value="">All Severity</option>
                @foreach($severities as $key => $severity)
                    <option value="{{ $key }}" {{ request('severity') == $key ? 'selected' : '' }}>
                        {{ $severity['label'] }}
                    </option>
                @endforeach
            </select>
            <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $key => $category)
                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
            @if(request()->hasAny(['status', 'severity', 'category']))
                <a href="{{ route('detection.alerts') }}" class="btn btn-secondary btn-sm">Clear Filters</a>
            @endif
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- Alerts Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Severity</th>
                    <th>Alert</th>
                    <th>Source</th>
                    <th>Rule</th>
                    <th>Events</th>
                    <th>Status</th>
                    <th>Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($alerts as $alert)
                <tr class="alert-row severity-{{ $alert->severity }}">
                    <td>
                        <span class="severity-indicator severity-{{ $alert->severity }}">
                            @switch($alert->severity)
                                @case('critical') 🔴 @break
                                @case('high') 🟠 @break
                                @case('medium') 🟡 @break
                                @case('low') 🟢 @break
                            @endswitch
                        </span>
                    </td>
                    <td>
                        <div class="alert-title">{{ $alert->title }}</div>
                        <div class="alert-desc">{{ Str::limit($alert->description, 80) }}</div>
                    </td>
                    <td>
                        <div class="source-info">
                            <span class="source-ip">{{ $alert->source_ip ?? 'N/A' }}</span>
                            @if($alert->source_user)
                                <span class="source-user">{{ $alert->source_user }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="rule-tag">{{ $alert->rule->name ?? 'Unknown' }}</span>
                    </td>
                    <td>
                        <span class="event-count">{{ $alert->event_count }}</span>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $alert->status }}">
                            {{ $statuses[$alert->status]['label'] ?? $alert->status }}
                        </span>
                    </td>
                    <td>
                        <div class="time-info">
                            <span class="time-ago">{{ $alert->created_at->diffForHumans() }}</span>
                            <span class="time-exact">{{ $alert->created_at->format('M d, H:i') }}</span>
                        </div>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="{{ route('detection.alerts.show', $alert) }}" class="btn btn-sm btn-icon" title="View Details">
                                👁️
                            </a>
                            @if($alert->status === 'new')
                            <form action="{{ route('detection.alerts.status', $alert) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="investigating">
                                <button type="submit" class="btn btn-sm btn-icon" title="Start Investigation">
                                    🔍
                                </button>
                            </form>
                            @endif
                            @if(in_array($alert->status, ['new', 'investigating']))
                            <form action="{{ route('detection.alerts.status', $alert) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="resolved">
                                <button type="submit" class="btn btn-sm btn-icon btn-success" title="Mark Resolved">
                                    ✅
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <div class="empty-icon">🛡️</div>
                        <div class="empty-text">No alerts found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container">
        {{ $alerts->withQueryString()->links() }}
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

.stat-card.stat-danger { border-left: 3px solid #ef4444; }
.stat-card.stat-critical { border-left: 3px solid #f97316; }
.stat-card.stat-success { border-left: 3px solid #22c55e; }

.stat-icon {
    font-size: 2rem;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
}

.stat-label {
    font-size: 0.8rem;
    color: #64748b;
}

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
    flex-wrap: wrap;
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
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 16px;
    border-top: 1px solid #2d3748;
    vertical-align: middle;
}

.alert-row:hover {
    background: rgba(0, 212, 255, 0.05);
}

.alert-row.severity-critical {
    border-left: 3px solid #ef4444;
}

.alert-row.severity-high {
    border-left: 3px solid #f97316;
}

.severity-indicator {
    font-size: 1.2rem;
}

.alert-title {
    font-weight: 600;
    color: #fff;
    margin-bottom: 4px;
}

.alert-desc {
    font-size: 0.8rem;
    color: #64748b;
}

.source-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.source-ip {
    font-family: monospace;
    color: #00d4ff;
    font-size: 0.85rem;
}

.source-user {
    font-size: 0.8rem;
    color: #94a3b8;
}

.rule-tag {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.event-count {
    background: rgba(0, 212, 255, 0.2);
    color: #00d4ff;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-new { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.status-investigating { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.status-resolved { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.status-false_positive { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.status-escalated { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }

.time-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.time-ago {
    font-size: 0.85rem;
    color: #e2e8f0;
}

.time-exact {
    font-size: 0.75rem;
    color: #64748b;
}

.action-buttons {
    display: flex;
    gap: 8px;
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
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: rgba(0, 212, 255, 0.2);
}

.btn-icon.btn-success:hover {
    background: rgba(34, 197, 94, 0.3);
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
}

.pagination-container {
    margin-top: 20px;
    display: flex;
    justify-content: center;
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
}

.btn-secondary {
    background: #374151;
    color: #e2e8f0;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
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
