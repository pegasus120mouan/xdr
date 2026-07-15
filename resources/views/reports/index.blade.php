@extends('layouts.app')

@section('title', 'Reports - Wara XDR')

@section('content')
<div class="page-content reports-page">
    <div class="page-header">
        <div>
            <p class="reports-kicker">Rapport opérationnel</p>
            <h1 class="page-title">Security Reports</h1>
        </div>
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

    <section class="report-brand">
        <div class="report-brand-main">
            <div class="report-logo-wrap">
                @if($selectedGroup?->logo_url)
                    <img src="{{ $selectedGroup->logo_url }}" alt="Logo {{ $selectedGroup->name }}" class="report-logo">
                @else
                    <img src="{{ asset('images/logo.png') }}" alt="Wara XDR" class="report-logo report-logo--fallback">
                @endif
            </div>
            <div>
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
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
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

    <div class="reports-kpi-grid">
        <article class="reports-kpi">
            <span class="reports-kpi__label">Assets</span>
            <strong class="reports-kpi__value">{{ number_format($stats['assets_total']) }}</strong>
        </article>
        <article class="reports-kpi reports-kpi--ok">
            <span class="reports-kpi__label">En ligne</span>
            <strong class="reports-kpi__value">{{ number_format($stats['assets_online']) }}</strong>
        </article>
        <article class="reports-kpi reports-kpi--warn">
            <span class="reports-kpi__label">Alertes ouvertes</span>
            <strong class="reports-kpi__value">{{ number_format($stats['alerts_open']) }}</strong>
        </article>
        <article class="reports-kpi reports-kpi--hot">
            <span class="reports-kpi__label">Critiques ouvertes</span>
            <strong class="reports-kpi__value">{{ number_format($stats['alerts_critical']) }}</strong>
        </article>
        <article class="reports-kpi reports-kpi--cyan">
            <span class="reports-kpi__label">IP bloquées</span>
            <strong class="reports-kpi__value">{{ number_format($stats['blocked_ips']) }}</strong>
        </article>
    </div>

    <div class="reports-panels">
        <section class="reports-panel">
            <header class="reports-panel__head">
                <h3>Couverture assets</h3>
            </header>
            <div class="reports-metric-grid">
                <div class="reports-metric">
                    <span class="reports-metric__label">Hors ligne</span>
                    <strong>{{ number_format($stats['assets_offline']) }}</strong>
                </div>
                <div class="reports-metric reports-metric--warn">
                    <span class="reports-metric__label">En alerte</span>
                    <strong>{{ number_format($stats['assets_alerting']) }}</strong>
                </div>
                <div class="reports-metric reports-metric--hot">
                    <span class="reports-metric__label">Critiques</span>
                    <strong>{{ number_format($stats['assets_critical']) }}</strong>
                </div>
                <div class="reports-metric reports-metric--amber">
                    <span class="reports-metric__label">Risque élevé</span>
                    <strong>{{ number_format($stats['assets_risky']) }}</strong>
                </div>
            </div>
        </section>

        <section class="reports-panel">
            <header class="reports-panel__head">
                <h3>Activité alertes ({{ $periodDays }}j)</h3>
            </header>
            <div class="reports-metric-grid reports-metric-grid--dense">
                <div class="reports-metric">
                    <span class="reports-metric__label">Total période</span>
                    <strong>{{ number_format($stats['alerts_total']) }}</strong>
                </div>
                <div class="reports-metric reports-metric--ok">
                    <span class="reports-metric__label">Résolues</span>
                    <strong>{{ number_format($stats['alerts_resolved']) }}</strong>
                </div>
                @foreach(['critical' => 'Critique', 'high' => 'Élevée', 'medium' => 'Moyenne', 'low' => 'Faible', 'info' => 'Info'] as $sev => $label)
                    <div class="reports-metric reports-metric--sev-{{ $sev }}">
                        <span class="reports-metric__label">{{ $label }}</span>
                        <strong>{{ number_format($severityBreakdown[$sev] ?? 0) }}</strong>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="reports-panel reports-panel--table">
        <header class="reports-panel__head">
            <h3>Alertes récentes</h3>
            <a href="{{ route('detection.alerts') }}" class="reports-link">Voir toutes →</a>
        </header>
        <div class="reports-table-wrap">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th style="width:110px">Sévérité</th>
                        <th>Titre</th>
                        <th style="width:130px">Statut</th>
                        <th style="width:160px">Tenant</th>
                        <th style="width:140px">Dernière vue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAlerts as $alert)
                        <tr>
                            <td><span class="rpt-pill rpt-pill--{{ $alert->severity }}">{{ $alert->severity }}</span></td>
                            <td>
                                <a class="reports-row-link" href="{{ route('detection.alerts.show', $alert) }}">
                                    {{ \Illuminate\Support\Str::limit($alert->title ?? 'Alerte', 70) }}
                                </a>
                            </td>
                            <td><span class="rpt-status">{{ $alert->status }}</span></td>
                            <td>{{ $alert->tenantGroup?->name ?? '—' }}</td>
                            <td class="reports-mono">{{ optional($alert->last_seen)->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="reports-empty">Aucune alerte sur la période sélectionnée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="reports-panel reports-panel--table">
        <header class="reports-panel__head">
            <h3>Assets à risque</h3>
            <a href="{{ route('tenants.index') }}" class="reports-link">Tous les tenants →</a>
        </header>
        <div class="reports-table-wrap">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>Hostname</th>
                        <th style="width:150px">IP</th>
                        <th style="width:120px">Statut</th>
                        <th style="width:160px">Risque</th>
                        <th style="width:160px">Tenant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riskyAssets as $asset)
                        <tr>
                            <td>
                                <a class="reports-row-link" href="{{ route('tenants.assets.show', $asset) }}">
                                    {{ $asset->hostname }}
                                </a>
                            </td>
                            <td class="reports-mono">{{ $asset->ip_address ?? '—' }}</td>
                            <td>
                                <span class="rpt-status rpt-status--{{ $asset->status }}">{{ $asset->status }}</span>
                            </td>
                            <td>
                                <span class="rpt-pill rpt-pill--{{ $asset->risk_level }}">{{ $asset->risk_level }}</span>
                                @if($asset->is_critical)
                                    <span class="rpt-flag">critique</span>
                                @endif
                            </td>
                            <td>{{ $asset->tenantGroup?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="reports-empty">Aucun asset à risque.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .reports-page { max-width: 1180px; }

    .reports-kicker {
        margin: 0 0 4px;
        font-size: 0.7rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .reports-header-actions {
        display: flex;
        align-items: flex-end;
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
        font-size: 0.68rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .reports-filter select {
        background: #151a24;
        border: 1px solid #2d3748;
        border-radius: 8px;
        color: #e2e8f0;
        padding: 8px 12px;
        font-size: 0.85rem;
        min-width: 170px;
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
        margin-bottom: 18px;
        padding: 18px 20px;
        border-radius: 12px;
        background: linear-gradient(165deg, #1a2332 0%, #12171f 100%);
        border: 1px solid #2d3748;
        flex-wrap: wrap;
    }

    .report-brand-main {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }

    .report-logo-wrap {
        width: 64px;
        height: 64px;
        border-radius: 10px;
        background: #0b0f16;
        border: 1px solid #2d3748;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }

    .report-logo {
        max-width: 52px;
        max-height: 52px;
        object-fit: contain;
    }

    .report-logo--fallback { opacity: 0.8; }

    .report-tenant-name {
        margin: 0 0 6px;
        font-size: 1.2rem;
        font-weight: 650;
        color: #f1f5f9;
    }

    .report-meta {
        margin: 0;
        font-size: 0.8rem;
        color: #94a3b8;
    }

    .report-logo-form,
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
        font-size: 0.68rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .logo-upload-label input[type="file"] {
        font-size: 0.78rem;
        color: #cbd5e1;
        max-width: 220px;
    }

    .report-logo-hint {
        margin: 0;
        font-size: 0.8rem;
        color: #64748b;
    }

    .reports-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }

    .reports-kpi {
        padding: 14px 16px;
        border-radius: 12px;
        background: #151a24;
        border: 1px solid #2d3748;
        border-left: 3px solid #64748b;
    }

    .reports-kpi--ok { border-left-color: #22c55e; }
    .reports-kpi--warn { border-left-color: #f97316; }
    .reports-kpi--hot { border-left-color: #ef4444; }
    .reports-kpi--cyan { border-left-color: #22d3ee; }

    .reports-kpi__label {
        display: block;
        font-size: 0.68rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 6px;
    }

    .reports-kpi__value {
        display: block;
        font-size: 1.55rem;
        font-weight: 700;
        color: #f8fafc;
        font-variant-numeric: tabular-nums;
        line-height: 1;
    }

    .reports-kpi--ok .reports-kpi__value { color: #86efac; }
    .reports-kpi--warn .reports-kpi__value { color: #fdba74; }
    .reports-kpi--hot .reports-kpi__value { color: #fca5a5; }
    .reports-kpi--cyan .reports-kpi__value { color: #67e8f9; }

    .reports-panels {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 14px;
    }

    .reports-panel {
        background: #151a24;
        border: 1px solid #2d3748;
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 14px;
    }

    .reports-panel--table { padding: 0; overflow: hidden; }

    .reports-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .reports-panel--table .reports-panel__head {
        margin: 0;
        padding: 14px 18px;
        border-bottom: 1px solid #2d3748;
    }

    .reports-panel__head h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #e2e8f0;
    }

    .reports-link {
        font-size: 0.78rem;
        color: #22d3ee;
        text-decoration: none;
        font-weight: 500;
    }

    .reports-link:hover { text-decoration: underline; color: #67e8f9; }

    .reports-metric-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .reports-metric-grid--dense {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reports-metric {
        padding: 12px 14px;
        border-radius: 10px;
        background: #0f1419;
        border: 1px solid #243044;
    }

    .reports-metric__label {
        display: block;
        font-size: 0.72rem;
        color: #94a3b8;
        margin-bottom: 6px;
    }

    .reports-metric strong {
        font-size: 1.2rem;
        color: #f1f5f9;
        font-variant-numeric: tabular-nums;
    }

    .reports-metric--ok strong { color: #86efac; }
    .reports-metric--warn strong { color: #fdba74; }
    .reports-metric--hot strong,
    .reports-metric--sev-critical strong { color: #fca5a5; }
    .reports-metric--amber strong,
    .reports-metric--sev-high strong { color: #fdba74; }
    .reports-metric--sev-medium strong { color: #fde68a; }
    .reports-metric--sev-low strong { color: #86efac; }
    .reports-metric--sev-info strong { color: #67e8f9; }

    .reports-table-wrap { overflow-x: auto; }

    .reports-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        font-size: 0.82rem;
    }

    .reports-table th {
        text-align: left;
        padding: 10px 14px;
        font-size: 0.68rem;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #64748b;
        background: #12171f;
        border-bottom: 1px solid #2d3748;
        white-space: nowrap;
    }

    .reports-table td {
        padding: 12px 14px;
        border-bottom: 1px solid rgba(45, 55, 72, 0.55);
        color: #cbd5e1;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .reports-table tbody tr:hover td {
        background: rgba(34, 211, 238, 0.04);
    }

    .reports-table tbody tr:last-child td {
        border-bottom: none;
    }

    .reports-row-link {
        color: #22d3ee;
        text-decoration: none;
        font-weight: 500;
    }

    .reports-row-link:hover { text-decoration: underline; color: #67e8f9; }

    .reports-mono {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.78rem;
        color: #94a3b8;
    }

    .reports-empty {
        text-align: center !important;
        color: #64748b !important;
        padding: 28px 14px !important;
        white-space: normal !important;
    }

    .rpt-pill {
        display: inline-flex;
        align-items: center;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background: rgba(100, 116, 139, 0.2);
        color: #cbd5e1;
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .rpt-pill--critical,
    .rpt-pill--high { background: rgba(239,68,68,.16); color: #fca5a5; border-color: rgba(239,68,68,.28); }
    .rpt-pill--medium { background: rgba(234,179,8,.14); color: #fde68a; border-color: rgba(234,179,8,.28); }
    .rpt-pill--low,
    .rpt-pill--none { background: rgba(34,197,94,.12); color: #86efac; border-color: rgba(34,197,94,.25); }
    .rpt-pill--info { background: rgba(34,211,238,.12); color: #67e8f9; border-color: rgba(34,211,238,.25); }

    .rpt-status {
        display: inline-block;
        font-size: 0.75rem;
        color: #94a3b8;
        text-transform: capitalize;
    }

    .rpt-status--online { color: #86efac; }
    .rpt-status--offline { color: #94a3b8; }
    .rpt-status--alerting { color: #fdba74; }

    .rpt-flag {
        display: inline-block;
        margin-left: 6px;
        font-size: 0.65rem;
        color: #fca5a5;
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.25);
        border-radius: 999px;
        padding: 2px 6px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 8px;
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
        background: #151a24;
        color: #cbd5e1;
        border-color: #2d3748;
    }

    @media (max-width: 960px) {
        .reports-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .reports-panels { grid-template-columns: 1fr; }
    }

    @media print {
        .sidebar, .topbar, .alert-banner, .status-bar,
        .reports-header-actions, .report-logo-form, .report-logo-hint {
            display: none !important;
        }
        .main-content { margin-left: 0 !important; }
        .reports-panel, .reports-kpi, .report-brand {
            break-inside: avoid;
            box-shadow: none;
        }
        body { background: #fff; color: #111; }
        .page-content, .reports-panel, .reports-kpi, .report-brand {
            background: #fff !important;
            color: #111 !important;
            border-color: #ddd !important;
        }
        .reports-kpi__value, .report-tenant-name, .reports-panel__head h3,
        .reports-metric strong { color: #111 !important; }
        a { color: #111 !important; text-decoration: none !important; }
    }
</style>
@endpush
