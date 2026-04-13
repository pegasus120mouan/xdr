@extends('layouts.app')

@section('title', 'Detection Rules - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Detection Rules</h1>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-primary" onclick="document.getElementById('addRuleModal').style.display='flex'">
                + Add Rule
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @foreach($categories as $categoryKey => $categoryName)
        @if(isset($rules[$categoryKey]))
        <div class="rules-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="category-icon">
                        @switch($categoryKey)
                            @case('brute_force') 🔐 @break
                            @case('malware') 🦠 @break
                            @case('intrusion') 🚨 @break
                            @case('data_exfiltration') 📤 @break
                            @case('privilege_escalation') ⬆️ @break
                            @case('lateral_movement') ↔️ @break
                            @case('persistence') 🔄 @break
                            @case('command_control') 🎮 @break
                            @default 📋
                        @endswitch
                    </span>
                    {{ $categoryName }}
                </h2>
                <span class="rule-count">{{ $rules[$categoryKey]->count() }} rules</span>
            </div>

            <div class="rules-grid">
                @foreach($rules[$categoryKey] as $rule)
                <div class="rule-card {{ $rule->is_active ? '' : 'inactive' }}">
                    <div class="rule-header">
                        <div class="rule-info">
                            <h3 class="rule-name">{{ $rule->name }}</h3>
                            <span class="severity-badge severity-{{ $rule->severity }}">
                                {{ ucfirst($rule->severity) }}
                            </span>
                        </div>
                        <div class="rule-toggle">
                            @if(auth()->user()->isAdmin())
                            <form action="{{ route('detection.rules.toggle', $rule) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <label class="switch">
                                    <input type="checkbox" {{ $rule->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                    <span class="slider"></span>
                                </label>
                            </form>
                            @else
                            <label class="switch" style="opacity:0.5;cursor:not-allowed;" title="Réservé administrateur">
                                <input type="checkbox" {{ $rule->is_active ? 'checked' : '' }} disabled>
                                <span class="slider"></span>
                            </label>
                            @endif
                        </div>
                    </div>

                    <p class="rule-description">{{ $rule->description }}</p>

                    <div class="rule-params">
                        <div class="param">
                            <span class="param-label">Threshold</span>
                            <span class="param-value">{{ $rule->threshold }} attempts</span>
                        </div>
                        <div class="param">
                            <span class="param-label">Time Window</span>
                            <span class="param-value">{{ $rule->time_window / 60 }} min</span>
                        </div>
                        <div class="param">
                            <span class="param-label">Cooldown</span>
                            <span class="param-value">{{ $rule->cooldown / 3600 }} hr</span>
                        </div>
                    </div>

                    <div class="rule-mitre">
                        <span class="mitre-label">MITRE ATT&CK:</span>
                        @if($rule->mitre_techniques)
                            @foreach($rule->mitre_techniques as $technique)
                                <span class="mitre-tag">{{ $technique }}</span>
                            @endforeach
                        @endif
                    </div>

                    <div class="rule-actions-list">
                        <span class="actions-label">Actions:</span>
                        @foreach($rule->actions as $action)
                            <span class="action-tag action-{{ $action['type'] }}">
                                @switch($action['type'])
                                    @case('block_ip') 🚫 Block IP @break
                                    @case('notify') 📧 Notify @break
                                    @case('log') 📝 Log @break
                                    @default {{ $action['type'] }}
                                @endswitch
                            </span>
                        @endforeach
                    </div>

                    <div class="rule-footer">
                        @if(auth()->user()->isAdmin())
                        <button type="button" class="btn btn-sm btn-secondary" onclick="editRule({{ $rule->id }}, {{ json_encode($rule) }})">
                            ✏️ Edit
                        </button>
                        @endif
                        <span class="rule-stats">
                            {{ $rule->alerts()->count() }} alerts triggered
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
</div>

<!-- Edit Rule Modal -->
<div id="editRuleModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Rule</h2>
            <button class="modal-close" onclick="document.getElementById('editRuleModal').style.display='none'">&times;</button>
        </div>
        <form id="editRuleForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="modal-body">
                <div class="form-group">
                    <label>Rule Name</label>
                    <input type="text" id="edit_rule_name" class="form-input" disabled>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Threshold (attempts)</label>
                        <input type="number" name="threshold" id="edit_threshold" class="form-input" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Time Window (seconds)</label>
                        <input type="number" name="time_window" id="edit_time_window" class="form-input" min="60" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Severity</label>
                    <select name="severity" id="edit_severity" class="form-input">
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editRuleModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<style>
.rules-section {
    margin-bottom: 32px;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #2d3748;
}

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-icon {
    font-size: 1.2rem;
}

.rule-count {
    font-size: 0.85rem;
    color: #64748b;
    background: rgba(100, 116, 139, 0.2);
    padding: 4px 12px;
    border-radius: 12px;
}

.rules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 16px;
}

.rule-card {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.3s ease;
}

.rule-card:hover {
    border-color: #00d4ff;
}

.rule-card.inactive {
    opacity: 0.6;
}

.rule-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.rule-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.rule-name {
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
}

.severity-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-critical { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.severity-high { background: rgba(249, 115, 22, 0.2); color: #f97316; }
.severity-medium { background: rgba(234, 179, 8, 0.2); color: #eab308; }
.severity-low { background: rgba(34, 197, 94, 0.2); color: #22c55e; }

.switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #374151;
    transition: 0.3s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #22c55e;
}

input:checked + .slider:before {
    transform: translateX(20px);
}

.rule-description {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 16px;
    line-height: 1.5;
}

.rule-params {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}

.param {
    background: rgba(0, 212, 255, 0.1);
    padding: 8px 12px;
    border-radius: 6px;
}

.param-label {
    font-size: 0.7rem;
    color: #64748b;
    display: block;
}

.param-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: #00d4ff;
}

.rule-mitre {
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.mitre-label {
    font-size: 0.75rem;
    color: #64748b;
}

.mitre-tag {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-family: monospace;
}

.rule-actions-list {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.actions-label {
    font-size: 0.75rem;
    color: #64748b;
}

.action-tag {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.action-block_ip { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
.action-notify { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
.action-log { background: rgba(100, 116, 139, 0.2); color: #94a3b8; }

.rule-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #2d3748;
}

.rule-stats {
    font-size: 0.8rem;
    color: #64748b;
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

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 212, 255, 0.3);
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
    max-width: 500px;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
</style>

<script>
function editRule(id, rule) {
    document.getElementById('editRuleForm').action = '/detection/rules/' + id;
    document.getElementById('edit_rule_name').value = rule.name;
    document.getElementById('edit_threshold').value = rule.threshold;
    document.getElementById('edit_time_window').value = rule.time_window;
    document.getElementById('edit_severity').value = rule.severity;
    document.getElementById('editRuleModal').style.display = 'flex';
}
</script>
@endsection
