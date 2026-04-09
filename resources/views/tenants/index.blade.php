@extends('layouts.app')

@section('title', 'All Tenants - Athena XDR')

@section('content')
<div class="page-content tenants-page">
    <div class="page-header">
        <h1 class="page-title">All Tenants</h1>
        <button class="btn btn-primary" onclick="document.getElementById('addGroupModal').style.display='flex'">
            + Add Group
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="tenants-layout">
        <!-- Tree Sidebar -->
        <div class="tree-sidebar">
            <div class="tree-header">
                <span class="tree-title">📁 All Tenants</span>
                <span class="asset-count">{{ $stats['total_assets'] }} assets</span>
            </div>
            <div class="tree-container">
                <ul class="tree-root">
                    @include('tenants.partials.tree-item', ['groups' => $tree, 'level' => 0])
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-panel">
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-dot online"></span>
                    <span class="stat-label">Online:</span>
                    <span class="stat-value">{{ $stats['online'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-dot offline"></span>
                    <span class="stat-label">Offline:</span>
                    <span class="stat-value">{{ $stats['offline'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-dot alerting"></span>
                    <span class="stat-label">Alerting:</span>
                    <span class="stat-value">{{ $stats['alerting'] }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Groups:</span>
                    <span class="stat-value">{{ $stats['total_groups'] }}</span>
                </div>
            </div>

            <!-- Selected Group Info -->
            @if($selectedGroup)
            <div class="group-header">
                <div class="group-info">
                    <span class="group-icon">
                        @if($selectedGroup->type === 'folder') 📁
                        @elseif($selectedGroup->type === 'ip_range') 🌐
                        @else 🖥️
                        @endif
                    </span>
                    <div>
                        <h2 class="group-name">{{ $selectedGroup->name }}</h2>
                        <span class="group-path">{{ $selectedGroup->path }}</span>
                    </div>
                </div>
                <div class="group-actions">
                    <button class="btn btn-sm btn-secondary" onclick="editGroup({{ $selectedGroup->id }}, '{{ $selectedGroup->name }}')">
                        ✏️ Edit
                    </button>
                    @if(!$selectedGroup->is_system)
                    <form action="{{ route('tenants.groups.delete', $selectedGroup) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this group?')">
                            🗑️ Delete
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endif

            <!-- Assets Table -->
            <div class="assets-section">
                <div class="section-header">
                    <h3>{{ $selectedGroup ? $selectedGroup->name . ' Assets' : 'All Assets' }}</h3>
                    <span class="count">{{ $assets->total() }} assets</span>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Hostname</th>
                                <th>IP Address</th>
                                <th>Type</th>
                                <th>OS</th>
                                <th>Group</th>
                                <th>Risk</th>
                                <th>Last Seen</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $asset)
                            <tr>
                                <td>
                                    <span class="status-indicator status-{{ $asset->status }}">
                                        @switch($asset->status)
                                            @case('online') 🟢 @break
                                            @case('offline') ⚫ @break
                                            @case('alerting') 🟠 @break
                                            @default ⚪
                                        @endswitch
                                    </span>
                                </td>
                                <td>
                                    <div class="hostname-cell">
                                        <span class="device-icon">
                                            @switch($asset->type)
                                                @case('server') 🖥️ @break
                                                @case('laptop') 💻 @break
                                                @case('mobile') 📱 @break
                                                @default 💻
                                            @endswitch
                                        </span>
                                        <a href="{{ route('tenants.assets.show', $asset) }}" class="hostname-link">
                                            {{ $asset->hostname }}
                                        </a>
                                        @if($asset->is_critical)
                                            <span class="critical-badge">⭐</span>
                                        @endif
                                    </div>
                                </td>
                                <td><span class="ip-address">{{ $asset->ip_address }}</span></td>
                                <td>{{ ucfirst($asset->type) }}</td>
                                <td>
                                    <span class="os-badge os-{{ $asset->os_type }}">
                                        @switch($asset->os_type)
                                            @case('windows') 🪟 @break
                                            @case('linux') 🐧 @break
                                            @case('macos') 🍎 @break
                                            @default ❓
                                        @endswitch
                                        {{ ucfirst($asset->os_type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($asset->tenantGroup)
                                        <span class="group-tag">{{ $asset->tenantGroup->name }}</span>
                                    @else
                                        <span class="no-group">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="risk-badge risk-{{ $asset->risk_level }}">
                                        {{ ucfirst($asset->risk_level) }}
                                    </span>
                                </td>
                                <td>
                                    @if($asset->last_seen)
                                        <span class="last-seen" title="{{ $asset->last_seen->format('Y-m-d H:i:s') }}">
                                            {{ $asset->last_seen->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="never-seen">Never</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="{{ route('tenants.assets.show', $asset) }}" class="btn btn-sm btn-icon" title="View">👁️</a>
                                        <button class="btn btn-sm btn-icon" onclick="moveAsset({{ $asset->id }}, '{{ $asset->hostname }}')" title="Move">📁</button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <div class="empty-icon">🖥️</div>
                                    <div class="empty-text">No assets found</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination-container">
                    {{ $assets->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Group Modal -->
<div id="addGroupModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Add New Group</h2>
            <button class="modal-close" onclick="document.getElementById('addGroupModal').style.display='none'">&times;</button>
        </div>
        <form action="{{ route('tenants.groups.create') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>Group Name</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-input">
                        <option value="group">Group</option>
                        <option value="folder">Folder</option>
                        <option value="ip_range">IP Range</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Parent Group (optional)</label>
                    <select name="parent_id" class="form-input">
                        <option value="">None (Root level)</option>
                        @foreach(\App\Models\TenantGroup::orderBy('name')->get() as $group)
                            <option value="{{ $group->id }}">{{ $group->path }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-input" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addGroupModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Group</button>
            </div>
        </form>
    </div>
</div>

<!-- Move Asset Modal -->
<div id="moveAssetModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Move Asset</h2>
            <button class="modal-close" onclick="document.getElementById('moveAssetModal').style.display='none'">&times;</button>
        </div>
        <form id="moveAssetForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-body">
                <p>Moving: <strong id="moveAssetName"></strong></p>
                <div class="form-group">
                    <label>Select Destination Group</label>
                    <select name="tenant_group_id" class="form-input">
                        <option value="">Uncategorized</option>
                        @foreach(\App\Models\TenantGroup::orderBy('name')->get() as $group)
                            <option value="{{ $group->id }}">{{ $group->path }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('moveAssetModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Move Asset</button>
            </div>
        </form>
    </div>
</div>

<style>
.tenants-page {
    height: calc(100vh - 140px);
    display: flex;
    flex-direction: column;
}

.tenants-layout {
    display: flex;
    gap: 0;
    flex: 1;
    overflow: hidden;
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
}

.tree-sidebar {
    width: 280px;
    background: #151a24;
    border-right: 1px solid #2d3748;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.tree-header {
    padding: 16px;
    border-bottom: 1px solid #2d3748;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tree-title {
    font-weight: 600;
    color: #fff;
}

.asset-count {
    font-size: 0.75rem;
    color: #64748b;
    background: rgba(100, 116, 139, 0.2);
    padding: 2px 8px;
    border-radius: 10px;
}

.tree-container {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
}

.tree-root {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tree-item {
    user-select: none;
}

.tree-item-content {
    display: flex;
    align-items: center;
    padding: 6px 12px;
    cursor: pointer;
    transition: background 0.2s;
    color: #94a3b8;
    font-size: 0.85rem;
    text-decoration: none;
}

.tree-item-content:hover {
    background: rgba(0, 212, 255, 0.1);
    color: #fff;
}

.tree-item-content.active {
    background: rgba(0, 212, 255, 0.2);
    color: #00d4ff;
}

.tree-toggle {
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 4px;
    font-size: 0.7rem;
    color: #64748b;
}

.tree-icon {
    margin-right: 8px;
    font-size: 1rem;
}

.tree-name {
    flex: 1;
}

.tree-badge {
    font-size: 0.7rem;
    color: #64748b;
    background: rgba(100, 116, 139, 0.2);
    padding: 1px 6px;
    border-radius: 8px;
}

.tree-children {
    list-style: none;
    padding-left: 20px;
    margin: 0;
}

.main-panel {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.stats-bar {
    display: flex;
    gap: 24px;
    padding: 12px 20px;
    background: #1a1f2e;
    border-bottom: 1px solid #2d3748;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
}

.stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.stat-dot.online { background: #22c55e; }
.stat-dot.offline { background: #64748b; }
.stat-dot.alerting { background: #f97316; }

.stat-label {
    color: #64748b;
}

.stat-value {
    font-weight: 600;
    color: #fff;
}

.group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: rgba(0, 212, 255, 0.05);
    border-bottom: 1px solid #2d3748;
}

.group-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.group-icon {
    font-size: 1.5rem;
}

.group-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #fff;
    margin: 0;
}

.group-path {
    font-size: 0.8rem;
    color: #64748b;
}

.group-actions {
    display: flex;
    gap: 8px;
}

.assets-section {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #2d3748;
}

.section-header h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.count {
    font-size: 0.8rem;
    color: #64748b;
}

.table-container {
    flex: 1;
    overflow: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #151a24;
    padding: 10px 16px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    position: sticky;
    top: 0;
}

.data-table td {
    padding: 12px 16px;
    border-top: 1px solid #2d3748;
    font-size: 0.85rem;
}

.data-table tr:hover {
    background: rgba(0, 212, 255, 0.05);
}

.hostname-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.device-icon {
    font-size: 1.1rem;
}

.hostname-link {
    color: #00d4ff;
    text-decoration: none;
    font-weight: 500;
}

.hostname-link:hover {
    text-decoration: underline;
}

.critical-badge {
    color: #f97316;
}

.ip-address {
    font-family: monospace;
    color: #94a3b8;
}

.os-badge {
    font-size: 0.8rem;
}

.group-tag {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.no-group {
    color: #64748b;
}

.risk-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.risk-none { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.risk-low { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.risk-medium { background: rgba(234, 179, 8, 0.2); color: #eab308; }
.risk-high { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.risk-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.last-seen {
    color: #94a3b8;
    font-size: 0.8rem;
}

.never-seen {
    color: #64748b;
    font-style: italic;
}

.status-indicator {
    font-size: 0.9rem;
}

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

.btn-sm {
    padding: 6px 12px;
    font-size: 0.8rem;
}

.btn-icon {
    width: 28px;
    height: 28px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(100, 116, 139, 0.2);
    border: none;
    border-radius: 4px;
    cursor: pointer;
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
}

.pagination-container {
    padding: 16px 20px;
    border-top: 1px solid #2d3748;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
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
    margin: 0;
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

<script>
function moveAsset(id, name) {
    document.getElementById('moveAssetForm').action = '/tenants/assets/' + id + '/move';
    document.getElementById('moveAssetName').textContent = name;
    document.getElementById('moveAssetModal').style.display = 'flex';
}

function editGroup(id, name) {
    // Simple edit - could be expanded
    const newName = prompt('Enter new name:', name);
    if (newName && newName !== name) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/tenants/groups/' + id;
        form.innerHTML = '@csrf @method("PATCH") <input type="hidden" name="name" value="' + newName + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Tree toggle functionality
document.querySelectorAll('.tree-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const item = this.closest('.tree-item');
        const children = item.querySelector('.tree-children');
        if (children) {
            children.style.display = children.style.display === 'none' ? 'block' : 'none';
            this.textContent = children.style.display === 'none' ? '+' : '-';
        }
    });
});
</script>
@endsection
