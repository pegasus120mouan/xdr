@extends('layouts.app')

@section('title', 'Logs - ' . $agent->name . ' - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('agents.index') }}">Agents</a>
            <span>/</span>
            <a href="{{ route('agents.show', $agent) }}">{{ $agent->name }}</a>
            <span>/</span>
            <span>Logs</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <input type="text" name="search" class="filter-input" placeholder="Search logs..." value="{{ request('search') }}">
            <select name="severity" class="filter-select" onchange="this.form.submit()">
                <option value="">All Severity</option>
                @foreach($severities as $key => $sev)
                    <option value="{{ $key }}" {{ request('severity') == $key ? 'selected' : '' }}>
                        {{ $sev['label'] }}
                    </option>
                @endforeach
            </select>
            <select name="type" class="filter-select" onchange="this.form.submit()">
                <option value="">All Types</option>
                @foreach($logTypes as $key => $type)
                    <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            @if(request()->hasAny(['search', 'severity', 'type']))
                <a href="{{ route('agents.logs', $agent) }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <!-- Logs Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Severity</th>
                    <th>Type</th>
                    <th>Process</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="log-row severity-row-{{ $log->severity }}">
                    <td class="time-cell">
                        <div class="time-info">
                            <span class="time-main">{{ $log->log_timestamp ? $log->log_timestamp->format('H:i:s') : $log->created_at->format('H:i:s') }}</span>
                            <span class="time-date">{{ $log->log_timestamp ? $log->log_timestamp->format('Y-m-d') : $log->created_at->format('Y-m-d') }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="severity-badge severity-{{ $log->severity }}">
                            {{ ucfirst($log->severity) }}
                        </span>
                    </td>
                    <td><span class="type-badge">{{ $log->log_type }}</span></td>
                    <td class="process-cell">{{ $log->process ?? '-' }}</td>
                    <td class="message-cell">
                        <div class="message-content" onclick="toggleMessage(this)">
                            {{ $log->message }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty-state">
                        <div class="empty-icon">📭</div>
                        <div class="empty-text">No logs found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $logs->withQueryString()->links() }}
    </div>
</div>

<style>
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: #64748b;
}

.breadcrumb a {
    color: #00d4ff;
    text-decoration: none;
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

.filter-input,
.filter-select {
    padding: 8px 12px;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 0.85rem;
}

.filter-input {
    min-width: 200px;
}

.filter-select {
    min-width: 140px;
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
    padding: 12px 16px;
    border-top: 1px solid #2d3748;
    font-size: 0.85rem;
    vertical-align: top;
}

.log-row:hover {
    background: rgba(0, 212, 255, 0.05);
}

.severity-row-emergency,
.severity-row-alert,
.severity-row-critical {
    border-left: 3px solid #ef4444;
}

.severity-row-error {
    border-left: 3px solid #f97316;
}

.severity-row-warning {
    border-left: 3px solid #eab308;
}

.time-cell {
    white-space: nowrap;
}

.time-info {
    display: flex;
    flex-direction: column;
}

.time-main {
    font-family: monospace;
    color: #fff;
}

.time-date {
    font-size: 0.75rem;
    color: #64748b;
}

.severity-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

.severity-emergency, .severity-alert, .severity-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.severity-error { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.severity-warning { background: rgba(234, 179, 8, 0.2); color: #eab308; }
.severity-notice, .severity-info { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.severity-debug { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }

.type-badge {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.process-cell {
    font-family: monospace;
    color: #94a3b8;
    font-size: 0.8rem;
}

.message-cell {
    max-width: 500px;
}

.message-content {
    color: #e2e8f0;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 500px;
}

.message-content.expanded {
    white-space: normal;
    word-break: break-word;
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
</style>

<script>
function toggleMessage(el) {
    el.classList.toggle('expanded');
}
</script>
@endsection
