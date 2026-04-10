@extends('layouts.app')

@section('title', 'Login Attempts - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Login Attempts</h1>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['total_today'] }}</div>
                <div class="stat-label">Total Today</div>
            </div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-icon">❌</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['failed_today'] }}</div>
                <div class="stat-label">Failed Today</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🌐</div>
            <div class="stat-info">
                <div class="stat-value">{{ $stats['unique_ips'] }}</div>
                <div class="stat-label">Unique IPs</div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <input type="text" name="ip" class="filter-input" placeholder="Filter by IP..." value="{{ request('ip') }}">
            <input type="text" name="email" class="filter-input" placeholder="Filter by email..." value="{{ request('email') }}">
            <select name="status" class="filter-select">
                <option value="">All Status</option>
                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['ip', 'email', 'status']))
                <a href="{{ route('detection.login-attempts') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>IP Address</th>
                    <th>Email</th>
                    <th>User Agent</th>
                    <th>Status</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attempts as $attempt)
                <tr class="{{ $attempt->successful ? '' : 'row-failed' }}">
                    <td>
                        <div class="time-info">
                            <span class="time-main">{{ $attempt->attempted_at->format('H:i:s') }}</span>
                            <span class="time-date">{{ $attempt->attempted_at->format('Y-m-d') }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="ip-address">{{ $attempt->ip_address }}</span>
                    </td>
                    <td>{{ $attempt->email ?? 'N/A' }}</td>
                    <td>
                        <span class="user-agent" title="{{ $attempt->user_agent }}">
                            {{ Str::limit($attempt->user_agent, 40) }}
                        </span>
                    </td>
                    <td>
                        @if($attempt->successful)
                            <span class="status-badge status-success">✓ Success</span>
                        @else
                            <span class="status-badge status-failed">✗ Failed</span>
                        @endif
                    </td>
                    <td>{{ $attempt->failure_reason ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-state">
                        <div class="empty-icon">📋</div>
                        <div class="empty-text">No login attempts found</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $attempts->withQueryString()->links() }}
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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

.stat-card.stat-danger {
    border-left: 3px solid #ef4444;
}

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
    min-width: 180px;
}

.filter-select {
    min-width: 130px;
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
}

.row-failed {
    background: rgba(239, 68, 68, 0.05);
}

.time-info {
    display: flex;
    flex-direction: column;
}

.time-main {
    font-weight: 600;
    color: #fff;
}

.time-date {
    font-size: 0.75rem;
    color: #64748b;
}

.ip-address {
    font-family: monospace;
    color: #00d4ff;
    background: rgba(0, 212, 255, 0.1);
    padding: 4px 8px;
    border-radius: 4px;
}

.user-agent {
    font-size: 0.8rem;
    color: #94a3b8;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-success {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.status-failed {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
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
@endsection
