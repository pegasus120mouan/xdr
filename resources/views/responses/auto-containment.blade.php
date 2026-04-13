@extends('layouts.app')

@section('title', 'Auto Containment - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Auto Containment</h1>
        <a href="{{ route('responses.index') }}" class="btn btn-secondary">All responses</a>
    </div>

    <div class="containment-status-card {{ $blockingEnabled ? 'status-enforced' : 'status-monitor' }}">
        <div class="containment-status-header">
            <h2>Web login brute force</h2>
            @if($blockingEnabled)
                <span class="mode-pill mode-enforced">Enforcement active</span>
            @else
                <span class="mode-pill mode-monitor">Monitoring only</span>
            @endif
        </div>
        <p class="containment-status-text">
            @if($blockingEnabled)
                At least one active brute-force rule includes <strong>automatic IP blocking</strong>. Failed login attempts are counted per rule (IP and/or account window); when the threshold is exceeded, the client IP is blocked and a security alert is created.
            @else
                No active brute-force rule currently performs automatic IP blocking. Enable a rule with a <strong>Block IP</strong> action under Detection Rules, or turn on a disabled rule below.
            @endif
        </p>
        <dl class="containment-metrics">
            <div>
                <dt>Failed logins today</dt>
                <dd>{{ number_format($detectorStats['failed_attempts_today']) }}</dd>
            </div>
            <div>
                <dt>Active brute-force alerts</dt>
                <dd>{{ number_format($detectorStats['active_alerts']) }}</dd>
            </div>
            <div>
                <dt>Currently blocked IPs</dt>
                <dd>{{ number_format($detectorStats['blocked_ips']) }}</dd>
            </div>
        </dl>
    </div>

    <div class="section-header" style="margin-top: 2rem;">
        <h2 class="section-title">Brute force rules &amp; containment</h2>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rule</th>
                    <th>Threshold</th>
                    <th>Window</th>
                    <th>Blocks IP</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bruteRules as $rule)
                    @php
                        $blocks = collect($rule->actions ?? [])->contains(fn ($a) => ($a['type'] ?? '') === 'block_ip');
                    @endphp
                    <tr>
                        <td>
                            <div class="rule-cell-name">{{ $rule->name }}</div>
                            <div class="rule-cell-desc">{{ Str::limit($rule->description, 100) }}</div>
                        </td>
                        <td>{{ $rule->threshold }} fails</td>
                        <td>{{ $rule->time_window / 60 }} min</td>
                        <td>
                            @if($blocks)
                                <span class="status-badge status-active">Yes</span>
                            @else
                                <span class="status-badge status-expired">No</span>
                            @endif
                        </td>
                        <td>
                            @if($rule->is_active)
                                <span class="status-badge status-active">Active</span>
                            @else
                                <span class="status-badge status-inactive">Off</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p style="margin-top: 1.5rem; font-size: 0.85rem; color: #64748b;">
        Tune thresholds and windows under <a href="{{ route('detection.rules') }}" style="color: #38bdf8;">Detection Rules</a>. Manual blocks and unblock history are under <a href="{{ route('detection.blocked-ips') }}" style="color: #38bdf8;">Blocked IPs</a>.
    </p>
</div>

@push('styles')
<style>
    .containment-status-card {
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid #334155;
        background: #0f172a;
    }
    .containment-status-card.status-enforced {
        border-color: rgba(34, 197, 94, 0.35);
        box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.08);
    }
    .containment-status-card.status-monitor {
        border-color: rgba(234, 179, 8, 0.35);
    }
    .containment-status-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .containment-status-header h2 {
        margin: 0;
        font-size: 1.1rem;
        color: #f1f5f9;
    }
    .mode-pill {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
    }
    .mode-enforced {
        background: rgba(34, 197, 94, 0.2);
        color: #86efac;
    }
    .mode-monitor {
        background: rgba(234, 179, 8, 0.15);
        color: #fde047;
    }
    .containment-status-text {
        color: #94a3b8;
        line-height: 1.55;
        margin: 0 0 1.25rem;
        max-width: 48rem;
    }
    .containment-metrics {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin: 0;
    }
    .containment-metrics dt {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .containment-metrics dd {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #e2e8f0;
    }
    .rule-cell-name { font-weight: 600; color: #f1f5f9; }
    .rule-cell-desc { font-size: 0.8rem; color: #64748b; margin-top: 0.2rem; }
    .btn-secondary {
        background: #334155;
        color: #e2e8f0;
        border: 1px solid #475569;
    }
    .btn-secondary:hover { background: #475569; }
</style>
@endpush
@endsection
