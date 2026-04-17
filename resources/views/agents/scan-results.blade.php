@extends('layouts.app')

@section('title', 'Scan Results - ' . $agent->name . ' - Wara XDR')

@section('content')
<div class="page-content">
    <div class="page-header">
        <div class="breadcrumb">
            <a href="{{ route('agents.index') }}">Agents</a>
            <span>/</span>
            <a href="{{ route('agents.show', $agent) }}">{{ $agent->name }}</a>
            <span>/</span>
            <span>Vulnerability Scans</span>
        </div>
        <form action="{{ route('agents.scan', $agent) }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn-primary" onclick="return confirm('Lancer un nouveau scan ?')">
                🔍 Nouveau Scan
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <!-- Latest Scan Summary -->
    @if($latestScan)
    <div class="scan-summary">
        <h2>Dernier Scan Complété</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon">📅</div>
                <div class="summary-info">
                    <div class="summary-value">{{ $latestScan->completed_at?->format('d/m/Y H:i') }}</div>
                    <div class="summary-label">Date du scan</div>
                </div>
            </div>
            <div class="summary-card {{ $latestScan->critical_count > 0 ? 'critical' : '' }}">
                <div class="summary-icon">🔴</div>
                <div class="summary-info">
                    <div class="summary-value">{{ $latestScan->critical_count }}</div>
                    <div class="summary-label">Critiques</div>
                </div>
            </div>
            <div class="summary-card {{ $latestScan->high_count > 0 ? 'high' : '' }}">
                <div class="summary-icon">🟠</div>
                <div class="summary-info">
                    <div class="summary-value">{{ $latestScan->high_count }}</div>
                    <div class="summary-label">Élevées</div>
                </div>
            </div>
            <div class="summary-card {{ $latestScan->medium_count > 0 ? 'medium' : '' }}">
                <div class="summary-icon">🟡</div>
                <div class="summary-info">
                    <div class="summary-value">{{ $latestScan->medium_count }}</div>
                    <div class="summary-label">Moyennes</div>
                </div>
            </div>
            <div class="summary-card">
                <div class="summary-icon">🟢</div>
                <div class="summary-info">
                    <div class="summary-value">{{ $latestScan->low_count }}</div>
                    <div class="summary-label">Faibles</div>
                </div>
            </div>
        </div>

        <!-- Detailed Results -->
        @if($latestScan->results)
        <div class="results-sections">
            <!-- Packages -->
            @if(!empty($latestScan->results['packages']['findings']))
            <div class="result-section">
                <h3>📦 Paquets ({{ count($latestScan->results['packages']['findings']) }})</h3>
                <div class="findings-list">
                    @foreach(array_slice($latestScan->results['packages']['findings'], 0, 20) as $finding)
                    <div class="finding-item severity-{{ $finding['severity'] ?? 'low' }}">
                        <span class="finding-name">{{ $finding['name'] ?? 'Unknown' }}</span>
                        <span class="finding-version">{{ $finding['current_version'] ?? '' }}</span>
                        <span class="finding-type">{{ $finding['type'] ?? '' }}</span>
                        <span class="severity-badge">{{ ucfirst($finding['severity'] ?? 'low') }}</span>
                    </div>
                    @endforeach
                    @if(count($latestScan->results['packages']['findings']) > 20)
                    <div class="more-findings">+ {{ count($latestScan->results['packages']['findings']) - 20 }} autres...</div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Ports -->
            @if(!empty($latestScan->results['ports']['findings']))
            <div class="result-section">
                <h3>🔌 Ports Ouverts ({{ count($latestScan->results['ports']['findings']) }})</h3>
                <div class="findings-list">
                    @foreach($latestScan->results['ports']['findings'] as $finding)
                    <div class="finding-item severity-{{ $finding['severity'] ?? 'low' }}">
                        <span class="finding-port">Port {{ $finding['port'] ?? '?' }}</span>
                        <span class="finding-process">{{ $finding['process'] ?? '' }}</span>
                        @if($finding['exposed'] ?? false)
                        <span class="exposed-badge">Exposé</span>
                        @endif
                        <span class="finding-note">{{ $finding['note'] ?? '' }}</span>
                        <span class="severity-badge">{{ ucfirst($finding['severity'] ?? 'low') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Configuration -->
            @if(!empty($latestScan->results['config']['findings']))
            <div class="result-section">
                <h3>⚙️ Configuration ({{ count($latestScan->results['config']['findings']) }})</h3>
                <div class="findings-list">
                    @foreach($latestScan->results['config']['findings'] as $finding)
                    <div class="finding-item severity-{{ $finding['severity'] ?? 'low' }}">
                        <span class="finding-check">{{ $finding['check'] ?? 'Unknown' }}</span>
                        <span class="finding-status">{{ $finding['status'] ?? '' }}</span>
                        <span class="finding-recommendation">{{ $finding['recommendation'] ?? '' }}</span>
                        <span class="severity-badge">{{ ucfirst($finding['severity'] ?? 'low') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    <!-- Scan History -->
    <div class="scan-history">
        <h2>Historique des Scans</h2>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th>Vulnérabilités</th>
                        <th>Durée</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scans as $scan)
                    <tr>
                        <td><code>{{ $scan->scan_id }}</code></td>
                        <td>
                            <span class="status-badge status-{{ $scan->status }}">
                                @switch($scan->status)
                                    @case('pending') ⏳ En attente @break
                                    @case('running') 🔄 En cours @break
                                    @case('completed') ✅ Terminé @break
                                    @case('failed') ❌ Échoué @break
                                @endswitch
                            </span>
                        </td>
                        <td>{{ ucfirst($scan->scan_type) }}</td>
                        <td>
                            @if($scan->status === 'completed')
                            <span class="vuln-count">
                                @if($scan->critical_count > 0)<span class="critical">{{ $scan->critical_count }}C</span>@endif
                                @if($scan->high_count > 0)<span class="high">{{ $scan->high_count }}H</span>@endif
                                @if($scan->medium_count > 0)<span class="medium">{{ $scan->medium_count }}M</span>@endif
                                @if($scan->low_count > 0)<span class="low">{{ $scan->low_count }}L</span>@endif
                                @if($scan->vulnerabilities_found == 0)<span class="none">Aucune</span>@endif
                            </span>
                            @else
                            -
                            @endif
                        </td>
                        <td>{{ $scan->duration ?? '-' }}</td>
                        <td>{{ $scan->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            <div class="empty-icon">🔍</div>
                            <div class="empty-text">Aucun scan effectué</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="pagination-container">
            {{ $scans->links() }}
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
.breadcrumb a { color: #00d4ff; text-decoration: none; }

.scan-summary {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 24px;
}
.scan-summary h2 {
    margin: 0 0 20px;
    font-size: 1.2rem;
    color: #f1f5f9;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.summary-card {
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.summary-card.critical { border-color: #ef4444; background: rgba(239, 68, 68, 0.1); }
.summary-card.high { border-color: #f97316; background: rgba(249, 115, 22, 0.1); }
.summary-card.medium { border-color: #eab308; background: rgba(234, 179, 8, 0.1); }
.summary-icon { font-size: 1.5rem; }
.summary-value { font-size: 1.5rem; font-weight: 700; color: #fff; }
.summary-label { font-size: 0.75rem; color: #64748b; }

.results-sections {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.result-section {
    background: #0f1419;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 16px;
}
.result-section h3 {
    margin: 0 0 12px;
    font-size: 1rem;
    color: #e2e8f0;
}

.findings-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.finding-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    background: #1a1f2e;
    border-radius: 6px;
    font-size: 0.85rem;
    border-left: 3px solid #64748b;
}
.finding-item.severity-critical { border-left-color: #ef4444; }
.finding-item.severity-high { border-left-color: #f97316; }
.finding-item.severity-medium { border-left-color: #eab308; }
.finding-item.severity-low { border-left-color: #22c55e; }

.finding-name, .finding-check, .finding-port { font-weight: 600; color: #f1f5f9; min-width: 150px; }
.finding-version, .finding-process { color: #94a3b8; }
.finding-type, .finding-status { color: #64748b; font-size: 0.8rem; }
.finding-recommendation, .finding-note { color: #94a3b8; font-size: 0.8rem; flex: 1; }

.severity-badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    margin-left: auto;
}
.finding-item.severity-critical .severity-badge { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.finding-item.severity-high .severity-badge { background: rgba(249, 115, 22, 0.2); color: #fdba74; }
.finding-item.severity-medium .severity-badge { background: rgba(234, 179, 8, 0.2); color: #fde047; }
.finding-item.severity-low .severity-badge { background: rgba(34, 197, 94, 0.2); color: #86efac; }

.exposed-badge {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

.more-findings {
    text-align: center;
    color: #64748b;
    font-size: 0.85rem;
    padding: 8px;
}

.scan-history {
    background: #1a1f2e;
    border: 1px solid #2d3748;
    border-radius: 8px;
    padding: 24px;
}
.scan-history h2 {
    margin: 0 0 20px;
    font-size: 1.2rem;
    color: #f1f5f9;
}

.table-container {
    overflow-x: auto;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table th {
    background: #0f1419;
    padding: 12px 16px;
    text-align: left;
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
}
.data-table td {
    padding: 14px 16px;
    border-top: 1px solid #2d3748;
    font-size: 0.85rem;
}
.data-table code {
    background: #0f1419;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
}
.status-badge.status-pending { background: rgba(234, 179, 8, 0.2); color: #fde047; }
.status-badge.status-running { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.status-badge.status-completed { background: rgba(34, 197, 94, 0.2); color: #86efac; }
.status-badge.status-failed { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }

.vuln-count {
    display: flex;
    gap: 6px;
}
.vuln-count span {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}
.vuln-count .critical { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
.vuln-count .high { background: rgba(249, 115, 22, 0.2); color: #fdba74; }
.vuln-count .medium { background: rgba(234, 179, 8, 0.2); color: #fde047; }
.vuln-count .low { background: rgba(34, 197, 94, 0.2); color: #86efac; }
.vuln-count .none { color: #64748b; }

.empty-state {
    text-align: center;
    padding: 40px !important;
}
.empty-icon { font-size: 2rem; margin-bottom: 8px; }
.empty-text { color: #64748b; }

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}
.alert-success { background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #86efac; }
.alert-warning { background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.3); color: #fde047; }

@media (max-width: 1200px) {
    .summary-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .summary-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
@endsection
