@extends('layouts.app')

@section('title', 'Monitor Overview - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <h1 class="page-title">Overview</h1>
        <div class="date-range">
            <span class="date-input" style="cursor:default;">{{ $periodFrom }}</span>
            <span>to</span>
            <span class="date-input" style="cursor:default;">{{ $periodTo }}</span>
        </div>
    </div>

    <div class="cards-grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Issues</h3>
            </div>
            <div class="donut-container">
                <div class="donut-chart">
                    <canvas id="pendingChart"></canvas>
                    <div class="donut-center">
                        <div class="donut-value">{{ number_format($donutTotal) }}</div>
                        <div class="donut-label">Issues</div>
                    </div>
                </div>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-dot critical"></span>
                        <span>Open alerts</span>
                        <strong style="margin-left: auto;">{{ number_format($stats['pending_alerts']) }}</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot high"></span>
                        <span>Risky assets</span>
                        <strong style="margin-left: auto;">{{ number_format($stats['risky_assets']) }}</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot medium"></span>
                        <span>Critical open</span>
                        <strong style="margin-left: auto;">{{ number_format($stats['critical_open']) }}</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot low"></span>
                        <span>Blocked IPs</span>
                        <strong style="margin-left: auto;">{{ number_format($stats['blocked_ips']) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="progress-card">
            <div class="card-header">
                <h3 class="card-title">Progress (30 jours)</h3>
            </div>
            <div class="progress-header">
                <span class="progress-badge">✓</span>
                <span class="progress-text">
                    Alertes période: <span>{{ number_format($stats['alerts_30d']) }}</span>
                    · Résolues (7j): <span>{{ number_format($stats['resolved_week']) }}</span>
                </span>
            </div>
            <div class="stats-row" style="grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <div class="stat-card">
                    <div class="stat-value">{{ number_format($stats['resolved_week']) }}</div>
                    <div class="stat-label">Resolved (7d)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value success">{{ number_format($stats['fix_rate'], 1) }} %</div>
                    <div class="stat-label">Resolution ratio*</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ number_format($stats['blocked_ips']) }}</div>
                    <div class="stat-label">Blocked IPs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ number_format($stats['assets_online']) }}</div>
                    <div class="stat-label">Online assets</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value info">{{ number_format($stats['assets_total']) }}</div>
                    <div class="stat-label">Total assets</div>
                </div>
            </div>
            <p style="margin:12px 0 0;font-size:0.72rem;color:#64748b;">* Résolues 7j / alertes 30j (indicatif).</p>
        </div>
    </div>

    <div class="cards-grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Threat Response</h3>
                <a href="{{ route('detection.alerts') }}" class="btn-tab">Alertes</a>
            </div>
            <h4 style="font-size: 0.9rem; margin-bottom: 16px;">Security incidents (14 jours)</h4>
            <div style="display: flex; gap: 24px;">
                <div>
                    <div style="font-size: 2rem; font-weight: 700;">{{ number_format($stats['pending_alerts']) }}</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Open</div>
                    <div style="margin-top: 12px; font-size: 0.8rem;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span class="legend-dot critical"></span>
                            <span>Critical</span>
                            <strong style="margin-left: auto;">{{ number_format($stats['critical_open']) }}</strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span class="legend-dot high"></span>
                            <span>High</span>
                            <strong style="margin-left: auto;">{{ number_format($stats['high_open']) }}</strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span class="legend-dot medium"></span>
                            <span>Medium</span>
                            <strong style="margin-left: auto;">{{ number_format($stats['medium_open']) }}</strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="legend-dot low"></span>
                            <span>Low / Info</span>
                            <strong style="margin-left: auto;">{{ number_format($stats['low_open']) }}</strong>
                        </div>
                    </div>
                </div>
                <div style="flex: 1;">
                    <canvas id="incidentsChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <div class="card containment-card">
            <div class="containment-header">
                <h3 class="card-title">Auto Containment</h3>
                <div class="mode-badge">
                    <span>●</span>
                    {{ $blockingEnabled ? 'Enforcement' : 'Monitoring Mode' }}
                </div>
            </div>
            <div class="mode-warning">
                @if($blockingEnabled)
                    Blocage IP automatique actif sur au moins une règle brute-force.
                @else
                    Mode surveillance uniquement. Activez une règle avec action Block IP.
                @endif
            </div>
            <div class="containment-visual">
                <div class="threat-circle">
                    <div class="threat-value">{{ number_format($stats['blocked_ips']) }}</div>
                    <div class="threat-label">IP actuellement<br>bloquées</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; color: #64748b;">Assets alerting</div>
                    <div style="font-size: 1.2rem; font-weight: 600;">{{ number_format($stats['assets_alerting']) }}</div>
                </div>
                <div class="block-circle">
                    <div class="block-value">{{ $stats['assets_total'] > 0 ? number_format(($stats['assets_online'] / $stats['assets_total']) * 100, 1) : '0' }} %</div>
                    <div class="threat-label">Assets<br>online</div>
                </div>
            </div>
            <div style="margin-top:16px;">
                <a href="{{ route('responses.auto-containment') }}" class="btn-tab active">Configurer Auto Containment</a>
            </div>
        </div>
    </div>

    <div class="asset-section">
        <div class="asset-header">
            <h2 class="asset-title">Asset Protection</h2>
        </div>
        <div class="asset-grid">
            <div class="asset-card">
                <div class="card-header">
                    <h3 class="card-title">Assets</h3>
                    <a href="{{ route('tenants.index') }}" class="btn-tab">Tenants</a>
                </div>
                <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                    <div class="asset-stat">
                        <div class="asset-icon">💻</div>
                        <div class="asset-info">
                            <div class="value">{{ number_format($stats['assets_total']) }}</div>
                            <div class="label">Total Assets</div>
                        </div>
                    </div>
                    <div class="asset-stat">
                        <div class="asset-icon" style="background: rgba(239, 68, 68, 0.1);">⚠️</div>
                        <div class="asset-info">
                            <div class="value" style="color: #ef4444;">{{ number_format($stats['risky_assets']) }}</div>
                            <div class="label">Risky Assets</div>
                        </div>
                    </div>
                    <div class="asset-stat">
                        <div class="asset-icon" style="background: rgba(34, 197, 94, 0.1);">●</div>
                        <div class="asset-info">
                            <div class="value" style="color: #22c55e;">{{ number_format($stats['assets_online']) }}</div>
                            <div class="label">Online</div>
                        </div>
                    </div>
                    <div class="asset-stat">
                        <div class="asset-icon" style="background: rgba(100, 116, 139, 0.2);">○</div>
                        <div class="asset-info">
                            <div class="value">{{ number_format($stats['assets_offline']) }}</div>
                            <div class="label">Offline</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="asset-card" style="grid-column: span 2;">
                <div class="card-header">
                    <h3 class="card-title">Alert volume (14 jours)</h3>
                    <a href="{{ route('reports.index') }}" class="btn-tab">Reports</a>
                </div>
                <div class="trend-chart">
                    <canvas id="riskyAssetsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const pendingCtx = document.getElementById('pendingChart').getContext('2d');
    new Chart(pendingCtx, {
        type: 'doughnut',
        data: {
            labels: ['Open alerts', 'Risky assets', 'Critical open', 'Blocked IPs'],
            datasets: [{
                data: [
                    {{ (int) $donut['pending'] }},
                    {{ (int) $donut['risky'] }},
                    {{ (int) $donut['critical'] }},
                    {{ (int) $donut['blocked'] }}
                ],
                backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false } }
        }
    });

    const incidentsCtx = document.getElementById('incidentsChart').getContext('2d');
    new Chart(incidentsCtx, {
        type: 'line',
        data: {
            labels: @json($trendLabels),
            datasets: [{
                label: 'Alerts',
                data: @json($trendData),
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 2,
                pointBackgroundColor: '#f97316'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 10 } } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 10 } }, beginAtZero: true }
            }
        }
    });

    const riskyCtx = document.getElementById('riskyAssetsChart').getContext('2d');
    new Chart(riskyCtx, {
        type: 'line',
        data: {
            labels: @json($trendLabels),
            datasets: [{
                label: 'Daily alerts',
                data: @json($trendData),
                borderColor: '#00d4ff',
                backgroundColor: 'rgba(0, 212, 255, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b' } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b' }, beginAtZero: true }
            }
        }
    });
</script>
@endsection
