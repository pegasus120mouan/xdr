@extends('layouts.app')

@section('title', $asset->hostname . ' - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('tenants.index') }}">All Tenants</a>
            <span>/</span>
            @if($asset->tenantGroup)
                <a href="{{ route('tenants.index', ['group' => $asset->tenantGroup->id]) }}">{{ $asset->tenantGroup->name }}</a>
                <span>/</span>
            @endif
            <span>{{ $asset->hostname }}</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="asset-detail-grid">
        <!-- Main Info -->
        <div class="detail-card main-card">
            <div class="asset-header">
                <div class="asset-icon-large">
                    @switch($asset->type)
                        @case('server') 🖥️ @break
                        @case('laptop') 💻 @break
                        @case('mobile') 📱 @break
                        @case('network') 🌐 @break
                        @default 💻
                    @endswitch
                </div>
                <div class="asset-title-section">
                    <h1 class="asset-hostname">{{ $asset->hostname }}</h1>
                    <div class="asset-badges">
                        <span class="status-badge status-{{ $asset->status }}">
                            @switch($asset->status)
                                @case('online') 🟢 Online @break
                                @case('offline') ⚫ Offline @break
                                @case('alerting') 🟠 Alerting @break
                                @default ⚪ Unknown
                            @endswitch
                        </span>
                        <span class="risk-badge risk-{{ $asset->risk_level }}">
                            Risk: {{ ucfirst($asset->risk_level) }}
                        </span>
                        @if($asset->is_critical)
                            <span class="critical-badge">⭐ Critical Asset</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">IP Address</span>
                    <span class="info-value ip">{{ $asset->ip_address ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">MAC Address</span>
                    <span class="info-value">{{ $asset->mac_address ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Type</span>
                    <span class="info-value">{{ ucfirst($asset->type) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Operating System</span>
                    <span class="info-value">
                        @switch($asset->os_type)
                            @case('windows') 🪟 @break
                            @case('linux') 🐧 @break
                            @case('macos') 🍎 @break
                        @endswitch
                        {{ ucfirst($asset->os_type) }} {{ $asset->os_version }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Agent Version</span>
                    <span class="info-value">{{ $asset->agent_version ?? 'Not installed' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Group</span>
                    <span class="info-value">
                        @if($asset->tenantGroup)
                            <span class="group-tag">{{ $asset->tenantGroup->name }}</span>
                        @else
                            Uncategorized
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Seen</span>
                    <span class="info-value">
                        @if($asset->last_seen)
                            {{ $asset->last_seen->format('Y-m-d H:i:s') }}
                            <span class="time-ago">({{ $asset->last_seen->diffForHumans() }})</span>
                        @else
                            Never
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Agent Installed</span>
                    <span class="info-value">
                        @if($asset->agent_installed_at)
                            {{ $asset->agent_installed_at->format('Y-m-d') }}
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="detail-card">
            <h3 class="card-title">⚡ Actions</h3>
            <form action="{{ route('tenants.assets.update', $asset) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label>Hostname</label>
                    <input type="text" name="hostname" class="form-input" value="{{ $asset->hostname }}">
                </div>
                <div class="form-group">
                    <label>Group</label>
                    <select name="tenant_group_id" class="form-input">
                        <option value="">Uncategorized</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ $asset->tenant_group_id == $group->id ? 'selected' : '' }}>
                                {{ $group->path }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_critical" value="1" {{ $asset->is_critical ? 'checked' : '' }}>
                        Mark as Critical Asset
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>

            <div class="action-buttons-section">
                <button class="btn btn-secondary btn-block">🔄 Rescan Asset</button>
                <button class="btn btn-secondary btn-block">📋 View Logs</button>
                <button class="btn btn-danger btn-block">🔒 Isolate Asset</button>
            </div>
        </div>

        <!-- Security Status -->
        <div class="detail-card">
            <h3 class="card-title">🛡️ Security Status</h3>
            <div class="security-items">
                <div class="security-item">
                    <span class="security-icon good">✓</span>
                    <span class="security-label">Agent Status</span>
                    <span class="security-value">Active</span>
                </div>
                <div class="security-item">
                    <span class="security-icon good">✓</span>
                    <span class="security-label">Last Scan</span>
                    <span class="security-value">2 hours ago</span>
                </div>
                <div class="security-item">
                    <span class="security-icon {{ $asset->risk_level === 'none' ? 'good' : 'warning' }}">
                        {{ $asset->risk_level === 'none' ? '✓' : '⚠' }}
                    </span>
                    <span class="security-label">Risk Level</span>
                    <span class="security-value">{{ ucfirst($asset->risk_level) }}</span>
                </div>
                <div class="security-item">
                    <span class="security-icon good">✓</span>
                    <span class="security-label">Firewall</span>
                    <span class="security-value">Enabled</span>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="detail-card full-width">
            <h3 class="card-title">📋 Recent Activity</h3>
            <div class="activity-list">
                <div class="activity-item">
                    <span class="activity-time">10:32 AM</span>
                    <span class="activity-icon">🔍</span>
                    <span class="activity-text">Security scan completed - No threats found</span>
                </div>
                <div class="activity-item">
                    <span class="activity-time">09:15 AM</span>
                    <span class="activity-icon">🔄</span>
                    <span class="activity-text">Agent updated to version {{ $asset->agent_version }}</span>
                </div>
                <div class="activity-item">
                    <span class="activity-time">Yesterday</span>
                    <span class="activity-icon">🟢</span>
                    <span class="activity-text">Asset came online</span>
                </div>
            </div>
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

.breadcrumb a:hover {
    text-decoration: underline;
}

.asset-detail-grid {
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

.asset-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #2d3748;
}

.asset-icon-large {
    font-size: 3rem;
    width: 80px;
    height: 80px;
    background: rgba(0, 212, 255, 0.1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.asset-hostname {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 8px 0;
}

.asset-badges {
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

.status-online { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.status-offline { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.status-alerting { background: rgba(249, 115, 22, 0.2); color: #f97316; }

.risk-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.risk-none, .risk-low { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.risk-medium { background: rgba(234, 179, 8, 0.2); color: #eab308; }
.risk-high { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.risk-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.critical-badge {
    background: rgba(249, 115, 22, 0.2);
    color: #f97316;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
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

.card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #fff;
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

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-label input {
    width: 16px;
    height: 16px;
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
}

.btn-primary {
    background: linear-gradient(135deg, #0066cc 0%, #00d4ff 100%);
    color: #fff;
    width: 100%;
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
    margin-top: 8px;
}

.action-buttons-section {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #2d3748;
}

.security-items {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.security-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 6px;
}

.security-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
}

.security-icon.good {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
}

.security-icon.warning {
    background: rgba(234, 179, 8, 0.2);
    color: #eab308;
}

.security-label {
    flex: 1;
    color: #94a3b8;
    font-size: 0.85rem;
}

.security-value {
    color: #e2e8f0;
    font-size: 0.85rem;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 6px;
}

.activity-time {
    font-size: 0.8rem;
    color: #64748b;
    min-width: 80px;
}

.activity-icon {
    font-size: 1rem;
}

.activity-text {
    flex: 1;
    font-size: 0.85rem;
    color: #e2e8f0;
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
