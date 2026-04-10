@extends('layouts.app')

@section('title', 'Alert Details - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('detection.alerts') }}">Security Alerts</a>
            <span>/</span>
            <span>Alert #{{ $alert->id }}</span>
        </div>
    </div>

    <div class="alert-detail-grid">
        <!-- Main Info -->
        <div class="detail-card main-card">
            <div class="detail-header">
                <div class="alert-title-section">
                    <span class="severity-badge severity-{{ $alert->severity }}">
                        {{ ucfirst($alert->severity) }}
                    </span>
                    <h1 class="alert-title">{{ $alert->title }}</h1>
                </div>
                <span class="status-badge status-{{ $alert->status }}">
                    {{ ucfirst(str_replace('_', ' ', $alert->status)) }}
                </span>
            </div>

            <p class="alert-description">{{ $alert->description }}</p>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Detection Rule</span>
                    <span class="info-value">{{ $alert->rule->name ?? 'Unknown' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Category</span>
                    <span class="info-value">{{ ucfirst(str_replace('_', ' ', $alert->rule->category ?? 'N/A')) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Event Count</span>
                    <span class="info-value highlight">{{ $alert->event_count }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">First Seen</span>
                    <span class="info-value">{{ $alert->first_seen->format('Y-m-d H:i:s') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Seen</span>
                    <span class="info-value">{{ $alert->last_seen->format('Y-m-d H:i:s') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Duration</span>
                    <span class="info-value">{{ $alert->first_seen->diffForHumans($alert->last_seen, true) }}</span>
                </div>
            </div>

            <!-- MITRE ATT&CK Mapping -->
            @if($alert->mitre_mapping)
            <div class="mitre-section">
                <h3>MITRE ATT&CK Mapping</h3>
                <div class="mitre-tags">
                    @if(isset($alert->mitre_mapping['tactics']))
                        @foreach($alert->mitre_mapping['tactics'] as $tactic)
                            <span class="mitre-tag tactic">{{ $tactic }}</span>
                        @endforeach
                    @endif
                    @if(isset($alert->mitre_mapping['techniques']))
                        @foreach($alert->mitre_mapping['techniques'] as $technique)
                            <span class="mitre-tag technique">{{ $technique }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Source Info -->
        <div class="detail-card">
            <h3 class="card-title">🎯 Source Information</h3>
            <div class="source-details">
                <div class="source-item">
                    <span class="source-label">Source IP</span>
                    <span class="source-value ip">{{ $alert->source_ip ?? 'N/A' }}</span>
                </div>
                <div class="source-item">
                    <span class="source-label">Target IP</span>
                    <span class="source-value">{{ $alert->target_ip ?? 'N/A' }}</span>
                </div>
                <div class="source-item">
                    <span class="source-label">Source User</span>
                    <span class="source-value">{{ $alert->source_user ?? 'N/A' }}</span>
                </div>
                <div class="source-item">
                    <span class="source-label">Target User</span>
                    <span class="source-value">{{ $alert->target_user ?? 'N/A' }}</span>
                </div>
                <div class="source-item">
                    <span class="source-label">Affected Asset</span>
                    <span class="source-value">{{ $alert->affected_asset ?? 'N/A' }}</span>
                </div>
            </div>

            @if($alert->source_ip)
            <div class="ip-actions">
                <form action="{{ route('detection.blocked-ips.block') }}" method="POST">
                    @csrf
                    <input type="hidden" name="ip_address" value="{{ $alert->source_ip }}">
                    <input type="hidden" name="reason" value="Blocked from alert #{{ $alert->id }}">
                    <input type="hidden" name="duration" value="24">
                    <button type="submit" class="btn btn-danger">
                        🚫 Block IP (24h)
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="detail-card">
            <h3 class="card-title">⚡ Actions</h3>
            <form action="{{ route('detection.alerts.status', $alert) }}" method="POST" class="status-form">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label>Update Status</label>
                    <select name="status" class="form-input">
                        <option value="new" {{ $alert->status == 'new' ? 'selected' : '' }}>New</option>
                        <option value="investigating" {{ $alert->status == 'investigating' ? 'selected' : '' }}>Investigating</option>
                        <option value="resolved" {{ $alert->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="false_positive" {{ $alert->status == 'false_positive' ? 'selected' : '' }}>False Positive</option>
                        <option value="escalated" {{ $alert->status == 'escalated' ? 'selected' : '' }}>Escalated</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Resolution Notes</label>
                    <textarea name="resolution_notes" class="form-input" rows="3" placeholder="Add notes about this alert...">{{ $alert->resolution_notes }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Alert</button>
            </form>

            @if($alert->resolved_at)
            <div class="resolution-info">
                <p><strong>Resolved by:</strong> {{ $alert->resolvedByUser->name ?? 'Unknown' }}</p>
                <p><strong>Resolved at:</strong> {{ $alert->resolved_at->format('Y-m-d H:i:s') }}</p>
            </div>
            @endif
        </div>

        <!-- Raw Data -->
        @if($alert->raw_data)
        <div class="detail-card full-width">
            <h3 class="card-title">📋 Raw Data</h3>
            <pre class="raw-data">{{ json_encode($alert->raw_data, JSON_PRETTY_PRINT) }}</pre>
        </div>
        @endif

        <!-- Evidence -->
        @if($alert->evidence && count($alert->evidence) > 0)
        <div class="detail-card full-width">
            <h3 class="card-title">🔍 Evidence (Recent Login Attempts)</h3>
            <div class="evidence-table-container">
                <table class="evidence-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>IP Address</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alert->evidence as $attempt)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($attempt['attempted_at'])->format('H:i:s') }}</td>
                            <td class="ip">{{ $attempt['ip_address'] }}</td>
                            <td>{{ $attempt['email'] ?? 'N/A' }}</td>
                            <td>
                                <span class="status-dot {{ $attempt['successful'] ? 'success' : 'failed' }}"></span>
                                {{ $attempt['successful'] ? 'Success' : 'Failed' }}
                            </td>
                            <td>{{ $attempt['failure_reason'] ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
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

.breadcrumb a:hover {
    text-decoration: underline;
}

.alert-detail-grid {
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

.detail-card.main-card {
    grid-column: span 1;
}

.detail-card.full-width {
    grid-column: span 2;
}

.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.alert-title-section {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.alert-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #fff;
}

.severity-badge {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    width: fit-content;
}

.severity-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.severity-high { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.severity-medium { background: rgba(234, 179, 8, 0.2); color: #eab308; }
.severity-low { background: rgba(34, 197, 94, 0.2); color: #22c55e; }

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-new { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.status-investigating { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.status-resolved { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.status-false_positive { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.status-escalated { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }

.alert-description {
    color: #94a3b8;
    line-height: 1.6;
    margin-bottom: 24px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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
    font-weight: 500;
}

.info-value.highlight {
    color: #00d4ff;
    font-size: 1.2rem;
    font-weight: 700;
}

.mitre-section {
    border-top: 1px solid #2d3748;
    padding-top: 20px;
}

.mitre-section h3 {
    font-size: 0.9rem;
    color: #94a3b8;
    margin-bottom: 12px;
}

.mitre-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.mitre-tag {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-family: monospace;
}

.mitre-tag.tactic {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.mitre-tag.technique {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #fff;
}

.source-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.source-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #2d3748;
}

.source-label {
    color: #64748b;
    font-size: 0.85rem;
}

.source-value {
    color: #e2e8f0;
    font-size: 0.85rem;
}

.source-value.ip {
    font-family: monospace;
    color: #00d4ff;
}

.ip-actions {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #2d3748;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #0066cc 0%, #00d4ff 100%);
    color: #fff;
    width: 100%;
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid #ef4444;
    color: #ef4444;
    width: 100%;
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.3);
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 6px;
}

.form-input {
    width: 100%;
    padding: 10px 14px;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    color: #e2e8f0;
    font-size: 0.9rem;
}

.form-input:focus {
    outline: none;
    border-color: #00d4ff;
}

textarea.form-input {
    resize: vertical;
    min-height: 80px;
}

.resolution-info {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #2d3748;
    font-size: 0.85rem;
    color: #94a3b8;
}

.raw-data {
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 6px;
    padding: 16px;
    font-family: monospace;
    font-size: 0.8rem;
    color: #94a3b8;
    overflow-x: auto;
    white-space: pre-wrap;
}

.evidence-table-container {
    overflow-x: auto;
}

.evidence-table {
    width: 100%;
    border-collapse: collapse;
}

.evidence-table th {
    background: #0f1419;
    padding: 10px 12px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}

.evidence-table td {
    padding: 10px 12px;
    border-top: 1px solid #2d3748;
    font-size: 0.85rem;
}

.evidence-table .ip {
    font-family: monospace;
    color: #00d4ff;
}

.status-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
}

.status-dot.success { background: #22c55e; }
.status-dot.failed { background: #ef4444; }
</style>
@endsection
