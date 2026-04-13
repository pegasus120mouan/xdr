@extends('layouts.app')

@section('title', $agent->name . ' - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('agents.index') }}">Agents</a>
            <span>/</span>
            <span>{{ $agent->name }}</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="agent-detail-grid">
        <!-- Main Info -->
        <div class="detail-card main-card">
            <div class="agent-header">
                <div class="agent-icon-large">
                    @switch($agent->os_type)
                        @case('linux') 🐧 @break
                        @case('windows') 🪟 @break
                        @case('macos') 🍎 @break
                        @default 🖥️
                    @endswitch
                </div>
                <div class="agent-title-section">
                    <h1 class="agent-name">{{ $agent->name }}</h1>
                    <div class="agent-badges">
                        <span class="status-badge status-{{ $agent->status }}">
                            @switch($agent->status)
                                @case('active') 🟢 Active @break
                                @case('inactive') ⚫ Inactive @break
                                @case('pending') ⏳ Pending @break
                                @case('error') 🔴 Error @break
                            @endswitch
                        </span>
                        <span class="agent-id-badge">{{ $agent->agent_id }}</span>
                    </div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Hostname</span>
                    <span class="info-value">{{ $agent->hostname }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">IP Address</span>
                    <span class="info-value ip">{{ $agent->ip_address ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Operating System</span>
                    <span class="info-value">{{ ucfirst($agent->os_type) }} {{ $agent->os_version }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Group</span>
                    <span class="info-value">
                        @if($agent->tenantGroup)
                            <span class="group-tag">{{ $agent->tenantGroup->name }}</span>
                        @else
                            Unassigned
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Heartbeat</span>
                    <span class="info-value">
                        @if($agent->last_heartbeat)
                            {{ $agent->last_heartbeat->format('Y-m-d H:i:s') }}
                            <span class="time-ago">({{ $agent->last_heartbeat->diffForHumans() }})</span>
                        @else
                            Never
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Registered</span>
                    <span class="info-value">
                        @if($agent->registered_at)
                            {{ $agent->registered_at->format('Y-m-d H:i:s') }}
                        @else
                            Not yet
                        @endif
                    </span>
                </div>
            </div>

            <!-- Log Stats -->
            <div class="log-stats">
                <div class="log-stat">
                    <span class="log-stat-value">{{ number_format($logStats['total']) }}</span>
                    <span class="log-stat-label">Total Logs</span>
                </div>
                <div class="log-stat">
                    <span class="log-stat-value">{{ number_format($logStats['today']) }}</span>
                    <span class="log-stat-label">Today</span>
                </div>
                <div class="log-stat error">
                    <span class="log-stat-value">{{ number_format($logStats['errors']) }}</span>
                    <span class="log-stat-label">Errors</span>
                </div>
            </div>
        </div>

        <!-- Installation Script -->
        <div class="detail-card">
            <h3 class="card-title">🚀 Installation Script</h3>
            
            @if($agent->status === 'pending')
                @php
                    $isWindows = $agent->os_type === 'windows';
                    $downloadUrl = route('agents.install-script', $agent);
                    $installCommand = $isWindows
                        ? "powershell -ExecutionPolicy Bypass -Command \"iwr -UseBasicParsing {$downloadUrl} -OutFile xdr-agent-install.ps1; .\\\\xdr-agent-install.ps1\""
                        : "curl -sSL {$downloadUrl} -o xdr-agent-install.sh && sudo bash xdr-agent-install.sh";
                @endphp
                <div class="install-instructions">
                    <p>Run this command on your {{ $isWindows ? 'Windows machine' : 'Linux server' }}:</p>
                    <div class="code-block">
                        <code>{{ $installCommand }}</code>
                        <button class="copy-btn" onclick="copyToClipboard(this, @js($installCommand))">📋</button>
                    </div>
                    
                    <p class="or-text">Or download and run manually:</p>
                    <a href="{{ route('agents.install-script', $agent) }}" class="btn btn-primary btn-block">
                        ⬇️ Download {{ $isWindows ? 'PowerShell Script (.ps1)' : 'Install Script (.sh)' }}
                    </a>
                    
                    <div class="script-info">
                        <p><strong>API Key:</strong></p>
                        <div class="code-block small">
                            <code>{{ $agent->api_key }}</code>
                            <button class="copy-btn" onclick="copyToClipboard(this, '{{ $agent->api_key }}')">📋</button>
                        </div>
                    </div>
                </div>
            @else
                <div class="agent-active-info">
                    <div class="success-icon">✅</div>
                    <p>Agent is installed and {{ $agent->isOnline() ? 'online' : 'offline' }}</p>
                    <a href="{{ route('agents.install-script', $agent) }}" class="btn btn-secondary btn-sm">
                        ⬇️ Re-download Script
                    </a>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="detail-card">
            <h3 class="card-title">⚡ Actions</h3>
            <div class="action-buttons-section">
                <a href="{{ route('agents.logs', $agent) }}" class="btn btn-secondary btn-block">
                    📋 View All Logs
                </a>
                <button class="btn btn-secondary btn-block">
                    🔄 Force Sync
                </button>
                @if(auth()->user()->isAdmin())
                <form action="{{ route('agents.delete', $agent) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Delete this agent and all its logs?')">
                        🗑️ Delete Agent
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="detail-card full-width">
            <div class="card-header-row">
                <h3 class="card-title">📋 Recent Logs</h3>
                <a href="{{ route('agents.logs', $agent) }}" class="btn btn-sm btn-secondary">View All</a>
            </div>
            
            @if($recentLogs->count() > 0)
                <div class="logs-table-container">
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Severity</th>
                                <th>Type</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                            <tr>
                                <td class="time-cell">{{ $log->created_at->format('H:i:s') }}</td>
                                <td>
                                    <span class="severity-badge severity-{{ $log->severity }}">
                                        {{ ucfirst($log->severity) }}
                                    </span>
                                </td>
                                <td><span class="type-badge">{{ $log->log_type }}</span></td>
                                <td class="message-cell">{{ Str::limit($log->message, 100) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="no-logs">
                    <span class="no-logs-icon">📭</span>
                    <p>No logs received yet</p>
                </div>
            @endif
        </div>
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

.agent-detail-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.detail-card {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 24px;
}

.detail-card.full-width {
    grid-column: span 2;
}

.agent-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #2d3748;
}

.agent-icon-large {
    font-size: 3rem;
    width: 80px;
    height: 80px;
    background: rgba(0, 212, 255, 0.1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.agent-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 8px 0;
}

.agent-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.status-active { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.status-inactive { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.status-pending { background: rgba(234, 179, 8, 0.2); color: #eab308; }
.status-error { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.agent-id-badge {
    background: rgba(100, 116, 139, 0.2);
    color: #94a3b8;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-family: monospace;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.info-item {
    background: rgba(0, 0, 0, 0.2);
    padding: 12px;
    border-radius: 6px;
}

.info-label {
    display: block;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 4px;
}

.info-value {
    font-size: 0.9rem;
    color: #e2e8f0;
}

.info-value.ip {
    font-family: monospace;
    color: #00d4ff;
}

.time-ago {
    font-size: 0.8rem;
    color: #64748b;
}

.group-tag {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.log-stats {
    display: flex;
    gap: 16px;
    padding-top: 20px;
    border-top: 1px solid #2d3748;
}

.log-stat {
    flex: 1;
    text-align: center;
    padding: 16px;
    background: rgba(0, 212, 255, 0.1);
    border-radius: 8px;
}

.log-stat.error {
    background: rgba(239, 68, 68, 0.1);
}

.log-stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #00d4ff;
}

.log-stat.error .log-stat-value {
    color: #ef4444;
}

.log-stat-label {
    font-size: 0.8rem;
    color: #64748b;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #fff;
}

.install-instructions p {
    color: #94a3b8;
    font-size: 0.9rem;
    margin-bottom: 12px;
}

.code-block {
    display: flex;
    align-items: center;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 16px;
}

.code-block.small {
    padding: 8px 12px;
}

.code-block code {
    flex: 1;
    font-family: monospace;
    font-size: 0.8rem;
    color: #00d4ff;
    word-break: break-all;
}

.copy-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 8px;
    font-size: 1rem;
}

.or-text {
    text-align: center;
    color: #64748b;
    font-size: 0.85rem;
}

.script-info {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #2d3748;
}

.script-info p {
    margin-bottom: 8px;
}

.agent-active-info {
    text-align: center;
    padding: 20px;
}

.success-icon {
    font-size: 3rem;
    margin-bottom: 12px;
}

.agent-active-info p {
    color: #94a3b8;
    margin-bottom: 16px;
}

.action-buttons-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    text-decoration: none;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #0066cc 0%, #00d4ff 100%);
    color: #fff;
}

.btn-secondary {
    background: #374151;
    color: #e2e8f0;
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid #ef4444;
    color: #ef4444;
}

.btn-block {
    width: 100%;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
}

.card-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.card-header-row .card-title {
    margin-bottom: 0;
}

.logs-table-container {
    overflow-x: auto;
}

.logs-table {
    width: 100%;
    border-collapse: collapse;
}

.logs-table th {
    background: #0f1419;
    padding: 10px 12px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.logs-table td {
    padding: 10px 12px;
    border-top: 1px solid #2d3748;
    font-size: 0.85rem;
}

.time-cell {
    font-family: monospace;
    color: #64748b;
    white-space: nowrap;
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

.message-cell {
    color: #94a3b8;
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.no-logs {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

.no-logs-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 12px;
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

<script>
function copyToClipboard(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = '✓';
        setTimeout(() => btn.textContent = '📋', 2000);
    });
}
</script>
@endsection
