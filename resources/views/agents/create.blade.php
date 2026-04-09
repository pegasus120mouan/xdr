@extends('layouts.app')

@section('title', 'Deploy Agent - Athena XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('agents.index') }}">Agents</a>
            <span>/</span>
            <span>Deploy New Agent</span>
        </div>
    </div>

    <div class="deploy-container">
        <div class="deploy-card">
            <div class="card-header">
                <div class="card-icon">🚀</div>
                <div>
                    <h2>Deploy New Log Collection Agent</h2>
                    <p>Create an agent to collect logs from a Linux server</p>
                </div>
            </div>

            <form action="{{ route('agents.store') }}" method="POST">
                @csrf
                
                <div class="form-section">
                    <h3>Agent Configuration</h3>
                    
                    <div class="form-group">
                        <label>Agent Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-input" placeholder="e.g., Production Server 1" required value="{{ old('name') }}">
                        <span class="form-hint">A friendly name to identify this agent</span>
                    </div>

                    <div class="form-group">
                        <label>Tenant Group <span class="required">*</span></label>
                        <select name="tenant_group_id" class="form-input" required>
                            <option value="">Select a group...</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ ($selectedGroup && $selectedGroup->id == $group->id) || old('tenant_group_id') == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="form-hint">The group this agent belongs to</span>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Log Collection Settings</h3>
                    
                    <div class="log-types-grid">
                        <label class="log-type-option">
                            <input type="checkbox" name="log_types[]" value="syslog" checked>
                            <span class="log-type-card">
                                <span class="log-type-icon">📋</span>
                                <span class="log-type-name">System Logs</span>
                                <span class="log-type-desc">/var/log/syslog, messages</span>
                            </span>
                        </label>
                        <label class="log-type-option">
                            <input type="checkbox" name="log_types[]" value="auth" checked>
                            <span class="log-type-card">
                                <span class="log-type-icon">🔐</span>
                                <span class="log-type-name">Auth Logs</span>
                                <span class="log-type-desc">/var/log/auth.log, secure</span>
                            </span>
                        </label>
                        <label class="log-type-option">
                            <input type="checkbox" name="log_types[]" value="apache">
                            <span class="log-type-card">
                                <span class="log-type-icon">🌐</span>
                                <span class="log-type-name">Apache</span>
                                <span class="log-type-desc">Access & error logs</span>
                            </span>
                        </label>
                        <label class="log-type-option">
                            <input type="checkbox" name="log_types[]" value="nginx">
                            <span class="log-type-card">
                                <span class="log-type-icon">🌐</span>
                                <span class="log-type-name">Nginx</span>
                                <span class="log-type-desc">Access & error logs</span>
                            </span>
                        </label>
                        <label class="log-type-option">
                            <input type="checkbox" name="log_types[]" value="mysql">
                            <span class="log-type-card">
                                <span class="log-type-icon">🗄️</span>
                                <span class="log-type-name">MySQL</span>
                                <span class="log-type-desc">Database logs</span>
                            </span>
                        </label>
                        <label class="log-type-option">
                            <input type="checkbox" name="log_types[]" value="docker">
                            <span class="log-type-card">
                                <span class="log-type-icon">🐳</span>
                                <span class="log-type-name">Docker</span>
                                <span class="log-type-desc">Container logs</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('agents.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        🚀 Create Agent & Get Install Script
                    </button>
                </div>
            </form>
        </div>

        <div class="info-card">
            <h3>📖 How it works</h3>
            <ol class="steps-list">
                <li>
                    <span class="step-number">1</span>
                    <div>
                        <strong>Create the agent</strong>
                        <p>Configure the agent settings and select log types to collect</p>
                    </div>
                </li>
                <li>
                    <span class="step-number">2</span>
                    <div>
                        <strong>Download the script</strong>
                        <p>Get the installation script with your unique API key</p>
                    </div>
                </li>
                <li>
                    <span class="step-number">3</span>
                    <div>
                        <strong>Run on your server</strong>
                        <p>Execute the script with sudo on your Linux server</p>
                    </div>
                </li>
                <li>
                    <span class="step-number">4</span>
                    <div>
                        <strong>Start collecting</strong>
                        <p>Logs will be sent automatically to Athena XDR</p>
                    </div>
                </li>
            </ol>

            <div class="requirements">
                <h4>Requirements</h4>
                <ul>
                    <li>Linux server (Ubuntu, Debian, CentOS, RHEL)</li>
                    <li>Root/sudo access</li>
                    <li>curl installed</li>
                    <li>Network access to this server</li>
                </ul>
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

.deploy-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.deploy-card, .info-card {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 12px;
    padding: 24px;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #2d3748;
}

.card-icon {
    font-size: 2.5rem;
    width: 64px;
    height: 64px;
    background: rgba(0, 212, 255, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-header h2 {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0 0 4px 0;
}

.card-header p {
    color: #64748b;
    margin: 0;
    font-size: 0.9rem;
}

.form-section {
    margin-bottom: 28px;
}

.form-section h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: #94a3b8;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 0.9rem;
    color: #e2e8f0;
    margin-bottom: 8px;
    font-weight: 500;
}

.required {
    color: #ef4444;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 8px;
    color: #e2e8f0;
    font-size: 0.95rem;
}

.form-input:focus {
    outline: none;
    border-color: #00d4ff;
}

.form-hint {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 6px;
}

.log-types-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.log-type-option {
    cursor: pointer;
}

.log-type-option input {
    display: none;
}

.log-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    background: #0f1419;
    border: 2px solid #2d3748;
    border-radius: 8px;
    transition: all 0.2s;
    text-align: center;
}

.log-type-option input:checked + .log-type-card {
    border-color: #00d4ff;
    background: rgba(0, 212, 255, 0.1);
}

.log-type-icon {
    font-size: 1.5rem;
    margin-bottom: 8px;
}

.log-type-name {
    font-weight: 600;
    color: #fff;
    font-size: 0.9rem;
}

.log-type-desc {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 4px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #2d3748;
}

.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 0.9rem;
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

.info-card h3 {
    font-size: 1.1rem;
    margin-bottom: 20px;
}

.steps-list {
    list-style: none;
    padding: 0;
    margin: 0 0 24px 0;
}

.steps-list li {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}

.step-number {
    width: 28px;
    height: 28px;
    background: rgba(0, 212, 255, 0.2);
    color: #00d4ff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.steps-list strong {
    display: block;
    color: #fff;
    margin-bottom: 4px;
}

.steps-list p {
    color: #64748b;
    font-size: 0.85rem;
    margin: 0;
}

.requirements {
    background: rgba(0, 0, 0, 0.2);
    padding: 16px;
    border-radius: 8px;
}

.requirements h4 {
    font-size: 0.9rem;
    margin: 0 0 12px 0;
    color: #94a3b8;
}

.requirements ul {
    margin: 0;
    padding-left: 20px;
}

.requirements li {
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 6px;
}
</style>
@endsection
