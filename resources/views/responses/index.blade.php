@extends('layouts.app')

@section('title', 'Automated Responses - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Automated Responses</h1>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('responses.auto-containment') }}" class="btn btn-secondary">Auto Containment</a>
            <a href="{{ route('detection.blocked-ips') }}" class="btn btn-primary">Blocked IPs</a>
            <a href="{{ route('detection.rules') }}" class="btn btn-secondary">Detection Rules</a>
        </div>
    </div>

    <p class="page-intro">
        When a detection rule fires, these actions run automatically. Brute-force against the login form triggers IP blocking when rules include <strong>Block IP</strong> (see <em>Login Brute Force - IP Based</em> and related rules).
    </p>

    <div class="responses-stats">
        <div class="stat-card">
            <span class="stat-label">Active auto-block rules</span>
            <strong class="stat-value">{{ $stats['rules_with_auto_block'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">IPs blocked by automation</span>
            <strong class="stat-value">{{ $stats['system_blocked_ips'] }}</strong>
        </div>
        <div class="stat-card">
            <span class="stat-label">All active blocks</span>
            <strong class="stat-value">{{ $stats['active_blocked_ips'] }}</strong>
        </div>
    </div>

    <div class="section-header" style="margin-top: 2rem;">
        <h2 class="section-title">Response playbooks</h2>
        <span class="rule-count">{{ $rules->count() }} rules with block or notify</span>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rule</th>
                    <th>Category</th>
                    <th>Automated actions</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                <tr class="{{ $rule->is_active ? '' : 'row-inactive' }}">
                    <td>
                        <div class="rule-cell-name">{{ $rule->name }}</div>
                        <div class="rule-cell-desc">{{ Str::limit($rule->description, 120) }}</div>
                    </td>
                    <td>
                        <span class="category-pill">{{ $categories[$rule->category] ?? $rule->category }}</span>
                    </td>
                    <td>
                        <ul class="action-list">
                            @foreach($rule->actions ?? [] as $action)
                                @if(in_array($action['type'] ?? '', ['block_ip', 'notify', 'log']))
                                    <li>
                                        @switch($action['type'] ?? '')
                                            @case('block_ip')
                                                <span class="action-badge action-block">Block IP</span>
                                                @if(!empty($action['duration']))
                                                    <span class="action-meta">{{ $action['duration'] }}h</span>
                                                @else
                                                    <span class="action-meta">until manual unblock</span>
                                                @endif
                                                @break
                                            @case('notify')
                                                <span class="action-badge action-notify">Notify</span>
                                                @if(!empty($action['channel']))
                                                    <span class="action-meta">{{ $action['channel'] }}</span>
                                                @endif
                                                @break
                                            @case('log')
                                                <span class="action-badge action-log">Log</span>
                                                @break
                                        @endswitch
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </td>
                    <td>
                        @if($rule->is_active)
                            <span class="status-badge status-active">Active</span>
                        @else
                            <span class="status-badge status-inactive">Disabled</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-state">
                        <div class="empty-icon">🛡️</div>
                        <div class="empty-text">No response actions configured.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
    .page-intro {
        color: #94a3b8;
        font-size: 0.95rem;
        line-height: 1.5;
        max-width: 52rem;
        margin-bottom: 1.5rem;
    }
    .responses-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    .responses-stats .stat-card {
        background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid #334155;
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .responses-stats .stat-label {
        display: block;
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.35rem;
    }
    .responses-stats .stat-value {
        font-size: 1.5rem;
        color: #f1f5f9;
    }
    .rule-cell-name {
        font-weight: 600;
        color: #f1f5f9;
    }
    .rule-cell-desc {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 0.25rem;
    }
    .category-pill {
        display: inline-block;
        font-size: 0.75rem;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        background: #1e3a5f;
        color: #7dd3fc;
    }
    .action-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .action-list li {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }
    .action-badge {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.2rem 0.45rem;
        border-radius: 4px;
    }
    .action-block {
        background: rgba(239, 68, 68, 0.2);
        color: #fca5a5;
    }
    .action-notify {
        background: rgba(234, 179, 8, 0.15);
        color: #fde047;
    }
    .action-log {
        background: rgba(100, 116, 139, 0.25);
        color: #cbd5e1;
    }
    .action-meta {
        font-size: 0.75rem;
        color: #64748b;
    }
    tr.row-inactive td {
        opacity: 0.55;
    }
    .btn-secondary {
        background: #334155;
        color: #e2e8f0;
        border: 1px solid #475569;
    }
    .btn-secondary:hover {
        background: #475569;
    }
</style>
@endpush
@endsection
