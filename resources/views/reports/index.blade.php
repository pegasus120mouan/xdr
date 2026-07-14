@extends('layouts.app')

@section('title', 'Reports - Wara XDR')

@section('content')
<div class="page-content reports-page">
    <div class="page-header">
        <h1 class="page-title">Security Reports</h1>
        <div class="reports-header-actions">
            <form method="GET" action="{{ route('reports.index') }}" class="reports-filters">
                <label class="reports-filter">
                    <span>Tenant</span>
                    <select name="tenant" onchange="this.form.submit()">
                        @if(auth()->user()?->tenant_group_id === null)
                            <option value="" @selected(!$tenantId)>Tous les tenants</option>
                        @endif
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" @selected($tenantId === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="reports-filter">
                    <span>Période</span>
                    <select name="days" onchange="this.form.submit()">
                        <option value="7" @selected($periodDays === 7)>7 jours</option>
                        <option value="30" @selected($periodDays === 30)>30 jours</option>
                        <option value="90" @selected($periodDays === 90)>90 jours</option>
                    </select>
                </label>
            </form>
            <button type="button" class="btn btn-secondary" onclick="window.print()">Imprimer / PDF</button>
        </div>
    </div>

    @if(session('success'))
        <div class="reports-flash">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="reports-flash reports-flash--error">{{ $errors->first() }}</div>
    @endif

    <section class="report-brand card">
        <div class="report-brand-main">
            <div class="report-logo-wrap">
                @if($selectedGroup?->logo_url)
                    <img src="{{ $selectedGroup->logo_url }}" alt="Logo {{ $selectedGroup->name }}" class="report-logo">
                @else
                    <img src="{{ asset('images/logo.png') }}" alt="Wara XDR" class="report-logo report-logo--fallback">
                @endif
            </div>
            <div>
                <p class="report-kicker">Rapport opérationnel</p>
                <h2 class="report-tenant-name">
                    {{ $selectedGroup?->name ?? 'Vue consolidée — tous les tenants' }}
                </h2>
                <p class="report-meta">
                    Généré le {{ $generatedAt->format('d/m/Y H:i') }}
                    · Fenêtre {{ $periodDays }} jours
                    · {{ auth()->user()->name }}
                </p>
            </div>
        </div>

        @if($selectedGroup && auth()->user()?->isAdmin())
            <div class="report-logo-form">
                <form action="{{ route('reports.logo.update', $selectedGroup) }}" method="POST" enctype="multipart/form-data" class="logo-upload-form">
                    @csrf
                    <label class="logo-upload-label">
                        <span>Logo du tenant</span>
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml" required>
                    </label>
                    <button type="submit" class="btn btn-primary">Enregistrer le logo</button>
                </form>
                @if($selectedGroup->logo_path)
                    <form action="{{ route('reports.logo.destroy', $selectedGroup) }}" method="POST" onsubmit="return confirm('Supprimer le logo ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-secondary">Retirer</button>
                    </form>
                @endif
            </div>
        @elseif(!$selectedGroup)
            <p class="report-logo-hint">Sélectionnez un tenant pour afficher et gérer son logo.</p>
        @endif
    </section>

    <div class="stats-row reports-stats">
        <div class="stat-card">
            <div class="stat-value">{{ number_format($stats['assets_total']) }}</div>
            <div class="stat-label">Assets</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:#22c55e;">{{ number_format($stats['assets_online']) }}</div>
            <div class="stat-label">Online</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:#f97316;">{{ number_format($stats['alerts_open']) }}</div>
            <div class="stat-label">Alertes ouvertes</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:#ef4444;">{{ number_format($stats['alerts_critical']) }}</div>
            <div class="stat-label">Critiques ouvertes</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color:#00d4ff;">{{ number_format($stats['blocked_ips']) }}</div>
            <div class="stat-label">IP bloquées</div>
        </div>
    </div>

    <div class="cards-grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Couverture assets</h3>
            </div>
            <dl class="report-metrics">
                <div><dt>Offline</dt><dd>{{ number_format($stats['assets_offline']) }}</dd></div>
                <div><dt>Alerting</dt><dd>{{ number_format($stats['assets_alerting']) }}</dd></div>
                <div><dt>Critiques</dt><dd>{{ number_format($stats['assets_critical']) }}</dd></div>
                <div><dt>Risque élevé</dt><dd>{{ number_format($stats['assets_risky']) }}</dd></div>
            </dl>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Activité alertes ({{ $periodDays }}j)</h3>
            </div>
            <dl class="report-metrics">
                <div><dt>Total période</dt><dd>{{ number_format($stats['alerts_total']) }}</dd></div>
                <div><dt>Résolues</dt><dd>{{ number_format($stats['alerts_resolved']) }}</dd></div>
                @foreach(['critical','high','medium','low','info'] as $sev)
                    <div>
                        <dt>{{ ucfirst($sev) }}</dt>
                        <dd>{{ number_format($severityBreakdown[$sev] ?? 0) }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
        <div class="card-header">
            <h3 class="card-title">Alertes récentes</h3>
            <a href="{{ route('detection.alerts') }}" class="btn-tab">Voir toutes</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sévérité</th>
                        <th>Titre</th>
                        <th>Statut</th>
                        <th>Tenant</th>
                        <th>Dernière vue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAlerts as $alert)
                        <tr>
                            <td><span class="sev-pill sev-{{ $alert->severity }}">{{ $alert->severity }}</span></td>
                            <td>
                                <a href="{{ route('detection.alerts.show', $alert) }}" style="color:#00d4ff;text-decoration:none;">
                                    {{ \Illuminate\Support\Str::limit($alert->title ?? 'Alerte', 60) }}
                                </a>
                            </td>
                            <td>{{ $alert->status }}</td>
                            <td>{{ $alert->tenantGroup?->name ?? '—' }}</td>
                            <td>{{ optional($alert->last_seen)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="color:#64748b;">Aucune alerte sur la période.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Assets à risque</h3>
            <a href="{{ route('tenants.index') }}" class="btn-tab">All Tenants</a>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Hostname</th>
                        <th>IP</th>
                        <th>Statut</th>
                        <th>Risque</th>
                        <th>Tenant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riskyAssets as $asset)
                        <tr>
                            <td>
                                <a href="{{ route('tenants.assets.show', $asset) }}" style="color:#00d4ff;text-decoration:none;">
                                    {{ $asset->hostname }}
                                </a>
                            </td>
                            <td>{{ $asset->ip_address ?? '—' }}</td>
                            <td>{{ $asset->status }}</td>
                            <td>{{ $asset->risk_level }}@if($asset->is_critical) · critical asset @endif</td>
                            <td>{{ $asset->tenantGroup?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="color:#64748b;">Aucun asset à risque.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .reports-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .reports-filters {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .reports-filter {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 0.7rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .reports-filter select {
        background: #1a1f2e;
        border: 1px solid #2d3748;
        border-radius: 6px;
        color: #e2e8f0;
        padding: 6px 10px;
        font-size: 0.85rem;
        min-width: 160px;
    }
    .reports-flash {
        background: rgba(34, 197, 94, 0.12);
        border: 1px solid rgba(34, 197, 94, 0.35);
        color: #86efac;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 0.85rem;
    }
    .reports-flash--error {
        background: rgba(239, 68, 68, 0.12);
        border-color: rgba(239, 68, 68, 0.35);
        color: #fca5a5;
    }
    .report-brand {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .report-brand-main {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }
    .report-logo-wrap {
        width: 72px;
        height: 72px;
        border-radius: 10px;
        background: #0f1419;
        border: 1px solid #2d3748;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    .report-logo {
        max-width: 64px;
        max-height: 64px;
        object-fit: contain;
    }
    .report-logo--fallback {
        opacity: 0.75;
    }
    .report-kicker {
        margin: 0 0 4px;
        font-size: 0.7rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }
    .report-tenant-name {
        margin: 0 0 6px;
        font-size: 1.15rem;
        color: #e2e8f0;
    }
    .report-meta {
        margin: 0;
        font-size: 0.8rem;
        color: #94a3b8;
    }
    .report-logo-form {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }
    .logo-upload-form {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        flex-wrap: wrap;
    }
    .logo-upload-label {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 0.72rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .logo-upload-label input[type="file"] {
        font-size: 0.8rem;
        color: #cbd5e1;
        max-width: 220px;
    }
    .report-logo-hint {
        margin: 0;
        font-size: 0.8rem;
        color: #64748b;
    }
    .reports-stats {
        grid-template-columns: repeat(5, 1fr);
    }
    .report-metrics {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px 20px;
        margin: 0;
    }
    .report-metrics div {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        border-bottom: 1px solid rgba(45, 55, 72, 0.7);
        padding-bottom: 8px;
    }
    .report-metrics dt {
        color: #94a3b8;
        font-size: 0.8rem;
    }
    .report-metrics dd {
        margin: 0;
        font-weight: 600;
        color: #e2e8f0;
    }
    .sev-pill {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 0.7rem;
        text-transform: uppercase;
        background: #334155;
        color: #e2e8f0;
    }
    .sev-critical { background: rgba(239,68,68,.2); color: #fca5a5; }
    .sev-high { background: rgba(249,115,22,.2); color: #fdba74; }
    .sev-medium { background: rgba(234,179,8,.2); color: #fde68a; }
    .sev-low { background: rgba(34,197,94,.15); color: #86efac; }
    .sev-info { background: rgba(0,212,255,.12); color: #67e8f9; }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 0.8rem;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-primary {
        background: #00d4ff;
        color: #0f1419;
        font-weight: 600;
        border-color: #00d4ff;
    }
    .btn-secondary {
        background: #1a1f2e;
        color: #cbd5e1;
        border-color: #2d3748;
    }

    @media (max-width: 900px) {
        .reports-stats { grid-template-columns: repeat(2, 1fr); }
    }

    @media print {
        .sidebar, .topbar, .alert-banner, .status-bar,
        .reports-header-actions, .report-logo-form, .report-logo-hint,
        .btn-tab, form[action*="logout"] {
            display: none !important;
        }
        .main-content { margin-left: 0 !important; }
        .card, .stat-card, .report-brand {
            break-inside: avoid;
            box-shadow: none;
        }
        body { background: #fff; color: #111; }
        .page-content, .card, .stat-card { background: #fff !important; color: #111 !important; border-color: #ddd !important; }
        .stat-value, .report-tenant-name, .card-title, .report-metrics dd { color: #111 !important; }
        a { color: #111 !important; text-decoration: none !important; }
    }
</style>
@endpush
