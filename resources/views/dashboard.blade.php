@extends('layouts.app')

@section('title', 'Monitor Overview - Wara XDR')

@section('content')
<div class="page-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">Overview</h1>
        <div class="date-range">
            <input type="text" class="date-input" value="2026-03-11 00:00:00">
            <span>to</span>
            <input type="text" class="date-input" value="2026-04-09 23:59:59">
            <button style="background: none; border: none; color: #94a3b8; cursor: pointer;">🔄</button>
        </div>
    </div>

    <!-- Main Cards Grid -->
    <div class="cards-grid">
        <!-- Pending Issues Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Issues</h3>
            </div>
            <div class="donut-container">
                <div class="donut-chart">
                    <canvas id="pendingChart"></canvas>
                    <div class="donut-center">
                        <div class="donut-value">63</div>
                        <div class="donut-label">Medium</div>
                    </div>
                </div>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-dot critical"></span>
                        <span>Pending Incidents</span>
                        <strong style="margin-left: auto;">27</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot high"></span>
                        <span>Risky Assets</span>
                        <strong style="margin-left: auto;">12</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot medium"></span>
                        <span>High-Priority Vulns</span>
                        <strong style="margin-left: auto;">1</strong>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot low"></span>
                        <span>Pending Tickets</span>
                        <strong style="margin-left: auto;">0</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Card -->
        <div class="progress-card">
            <div class="card-header">
                <h3 class="card-title">Progress</h3>
            </div>
            <div class="progress-header">
                <span class="progress-badge">✓</span>
                <span class="progress-text">Total Risks Fixed: <span>10.5 k</span> , O&M Days: <span>622</span></span>
            </div>
            <div class="stats-row" style="grid-template-columns: repeat(5, 1fr); gap: 12px;">
                <div class="stat-card">
                    <div class="stat-value">193</div>
                    <div class="stat-label">Handled Incidents</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value success">88.1 %</div>
                    <div class="stat-label">Incident Fixing Rate</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">0</div>
                    <div class="stat-label">Blocked Hacker IPs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value info">29</div>
                    <div class="stat-label">Protected Assets</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Threat Response Section -->
    <div class="cards-grid">
        <!-- Security Incidents -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Threat Response</h3>
            </div>
            <h4 style="font-size: 0.9rem; margin-bottom: 16px;">Security Incidents</h4>
            <div class="card-actions" style="margin-bottom: 16px;">
                <button class="btn-tab active">Trend</button>
                <button class="btn-tab">List</button>
            </div>
            <div style="display: flex; gap: 24px;">
                <div>
                    <div style="font-size: 2rem; font-weight: 700;">27</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Pending</div>
                    <div style="margin-top: 12px; font-size: 0.8rem;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span class="legend-dot critical"></span>
                            <span>Critical</span>
                            <strong style="margin-left: auto;">1</strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span class="legend-dot high"></span>
                            <span>High</span>
                            <strong style="margin-left: auto;">10</strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                            <span class="legend-dot medium"></span>
                            <span>Medium</span>
                            <strong style="margin-left: auto;">11</strong>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="legend-dot low"></span>
                            <span>Low</span>
                            <strong style="margin-left: auto;">5</strong>
                        </div>
                    </div>
                </div>
                <div style="flex: 1;">
                    <canvas id="incidentsChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <!-- Auto Containment -->
        <div class="card containment-card">
            <div class="containment-header">
                <h3 class="card-title">Auto Containment</h3>
                <div class="mode-badge">
                    <span>●</span>
                    Monitoring Mode
                </div>
            </div>
            <div class="mode-warning">
                ⚠️ Current mode is monitoring only. Enabling automatic fixing is recommended.
            </div>
            <div class="containment-visual">
                <div class="threat-circle">
                    <div class="threat-value">5.3 k</div>
                    <div class="threat-label">Simulated Blocked<br>External Attackers</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 0.75rem; color: #64748b;">Average Block Time</div>
                    <div style="font-size: 1.2rem; font-weight: 600;">0 <span style="font-size: 0.8rem;">min</span></div>
                </div>
                <div class="block-circle">
                    <div class="block-value">38.87 %</div>
                    <div class="threat-label">Simulated External<br>Attacker Block</div>
                </div>
            </div>
            <div class="threat-entities">
                <div class="entity-title">External Threat Entities</div>
                <div class="entity-grid">
                    <div class="entity-item">
                        <span>IP Addresses</span>
                        <span class="entity-value">13.6 k</span>
                    </div>
                    <div class="entity-item">
                        <span>Domain Names</span>
                        <span class="entity-value">28</span>
                    </div>
                    <div class="entity-item">
                        <span>Blocked in Simulation</span>
                        <span class="entity-value">5.3 k</span>
                    </div>
                    <div class="entity-item">
                        <span>Monitored</span>
                        <span class="entity-value">1.2 k</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Asset Protection Section -->
    <div class="asset-section">
        <div class="asset-header">
            <h2 class="asset-title">Asset Protection</h2>
        </div>
        <div class="asset-grid">
            <!-- Assets Card -->
            <div class="asset-card">
                <div class="card-header">
                    <h3 class="card-title">Assets</h3>
                    <div class="card-actions">
                        <button class="btn-tab active">All</button>
                        <button class="btn-tab">Critical</button>
                    </div>
                </div>
                <div style="display: flex; gap: 24px;">
                    <div class="asset-stat">
                        <div class="asset-icon">💻</div>
                        <div class="asset-info">
                            <div class="value">918</div>
                            <div class="label">Total Assets</div>
                        </div>
                    </div>
                    <div class="asset-stat">
                        <div class="asset-icon" style="background: rgba(239, 68, 68, 0.1);">⚠️</div>
                        <div class="asset-info">
                            <div class="value" style="color: #ef4444;">12</div>
                            <div class="label">Risky Assets</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trend of Risky Assets -->
            <div class="asset-card" style="grid-column: span 2;">
                <div class="card-header">
                    <h3 class="card-title">Trend of Risky Assets</h3>
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
    // Pending Issues Donut Chart
    const pendingCtx = document.getElementById('pendingChart').getContext('2d');
    new Chart(pendingCtx, {
        type: 'doughnut',
        data: {
            labels: ['Pending Incidents', 'Risky Assets', 'High-Priority Vulns', 'Pending Tickets'],
            datasets: [{
                data: [27, 12, 1, 0],
                backgroundColor: ['#ef4444', '#f97316', '#eab308', '#22c55e'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // Security Incidents Line Chart
    const incidentsCtx = document.getElementById('incidentsChart').getContext('2d');
    new Chart(incidentsCtx, {
        type: 'line',
        data: {
            labels: ['03-11', '03-15', '03-19', '03-23', '03-27', '03-31', '04-04', '04-09'],
            datasets: [{
                label: 'Incidents',
                data: [2, 3, 5, 8, 6, 4, 7, 10],
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#f97316'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#64748b', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#64748b', font: { size: 10 } },
                    beginAtZero: true
                }
            }
        }
    });

    // Risky Assets Trend Chart
    const riskyCtx = document.getElementById('riskyAssetsChart').getContext('2d');
    new Chart(riskyCtx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Risky Assets',
                data: [8, 6, 5, 4],
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
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#64748b' }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: { color: '#64748b' },
                    beginAtZero: true
                }
            }
        }
    });
</script>
@endsection
