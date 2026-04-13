@extends('layouts.app')

@section('title', 'Blocked IPs - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Blocked IPs</h1>
        <button class="btn btn-primary" onclick="document.getElementById('blockIpModal').style.display='flex'">
            + Block IP
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Reason</th>
                    <th>Blocked By</th>
                    <th>Blocked Until</th>
                    <th>Status</th>
                    <th>Related Alert</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blockedIps as $blocked)
                <tr>
                    <td>
                        <span class="ip-address">{{ $blocked->ip_address }}</span>
                    </td>
                    <td>{{ $blocked->reason }}</td>
                    <td>{{ $blocked->blockedByUser->name ?? 'System' }}</td>
                    <td>
                        @if($blocked->blocked_until)
                            {{ $blocked->blocked_until->format('Y-m-d H:i') }}
                            <span class="time-remaining">
                                ({{ $blocked->blocked_until->diffForHumans() }})
                            </span>
                        @else
                            <span class="permanent">Permanent</span>
                        @endif
                    </td>
                    <td>
                        @if($blocked->is_active)
                            @if($blocked->blocked_until && $blocked->blocked_until->isPast())
                                <span class="status-badge status-expired">Expired</span>
                            @else
                                <span class="status-badge status-active">Active</span>
                            @endif
                        @else
                            <span class="status-badge status-inactive">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($blocked->alert)
                            <a href="{{ route('detection.alerts.show', $blocked->alert) }}" class="alert-link">
                                Alert #{{ $blocked->security_alert_id }}
                            </a>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @if($blocked->is_active && auth()->user()->isAdmin())
                        <form action="{{ route('detection.blocked-ips.unblock', $blocked) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to unblock this IP?')">
                                Unblock
                            </button>
                        </form>
                        @elseif($blocked->is_active)
                            <span class="text-muted" style="font-size:0.75rem;color:#64748b;">Déblocage : admin</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty-state">
                        <div class="empty-icon">🛡️</div>
                        <div class="empty-text">No blocked IPs</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-container">
        {{ $blockedIps->links() }}
    </div>
</div>

<!-- Block IP Modal -->
<div id="blockIpModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Block IP Address</h2>
            <button class="modal-close" onclick="document.getElementById('blockIpModal').style.display='none'">&times;</button>
        </div>
        <form action="{{ route('detection.blocked-ips.block') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>IP Address</label>
                    <input type="text" name="ip_address" class="form-input" placeholder="192.168.1.1" required>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <input type="text" name="reason" class="form-input" placeholder="Reason for blocking" required>
                </div>
                <div class="form-group">
                    <label>Duration (hours, leave empty for permanent)</label>
                    <input type="number" name="duration" class="form-input" placeholder="24" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('blockIpModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-danger">Block IP</button>
            </div>
        </form>
    </div>
</div>

<style>
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
    padding: 16px;
    border-top: 1px solid #2d3748;
}

.ip-address {
    font-family: monospace;
    color: #00d4ff;
    font-size: 0.9rem;
    background: rgba(0, 212, 255, 0.1);
    padding: 4px 8px;
    border-radius: 4px;
}

.time-remaining {
    font-size: 0.8rem;
    color: #64748b;
}

.permanent {
    color: #ef4444;
    font-weight: 600;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.status-inactive { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }
.status-expired { background: rgba(234, 179, 8, 0.2); color: #eab308; }

.alert-link {
    color: #00d4ff;
    text-decoration: none;
}

.alert-link:hover {
    text-decoration: underline;
}

.btn {
    padding: 8px 16px;
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
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid #ef4444;
    color: #ef4444;
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

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 12px;
    width: 100%;
    max-width: 450px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #2d3748;
}

.modal-header h2 {
    font-size: 1.2rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    color: #94a3b8;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #2d3748;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
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
</style>
@endsection
